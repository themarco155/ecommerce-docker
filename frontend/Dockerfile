# syntax=docker/dockerfile:1
# ============================================================
#  Frontend Next.js 16 — imagen de producción multi-stage
#  deps  → instala dependencias (capa cacheada por package-lock.json)
#  build → compila con `next build` en modo standalone
#  runner→ imagen final: solo server.js + módulos usados + estáticos
# ============================================================

ARG NODE_VERSION=22-alpine

# ---------- Base común ----------
FROM node:${NODE_VERSION} AS base
ENV NEXT_TELEMETRY_DISABLED=1
WORKDIR /app

# ---------- 1) Dependencias ----------
FROM base AS deps
# Solo los manifiestos: si no cambian, Docker reutiliza esta capa y no reinstala nada
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

# ---------- 2) Build ----------
FROM base AS build
COPY --from=deps /app/node_modules ./node_modules
COPY . .
# Las variables NEXT_PUBLIC_* se incrustan en el JavaScript del navegador al compilar
ARG NEXT_PUBLIC_STRIPE_KEY=""
ENV NEXT_PUBLIC_STRIPE_KEY=${NEXT_PUBLIC_STRIPE_KEY}
RUN npm run build

# ---------- 3) Runtime ----------
FROM base AS runner
ENV NODE_ENV=production \
    PORT=3000 \
    HOSTNAME=0.0.0.0

# Usuario sin privilegios (no corre como root)
RUN addgroup -S -g 1001 nodejs && adduser -S -u 1001 -G nodejs nextjs

# Solo lo necesario para el servidor de producción:
#   .next/standalone → server.js + node_modules mínimos (trazados por Next.js)
#   .next/static     → JS/CSS del navegador
#   public           → archivos públicos
COPY --from=build --chown=nextjs:nodejs /app/public ./public
COPY --from=build --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=build --chown=nextjs:nodejs /app/.next/static ./.next/static

USER nextjs
EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD wget -qO- http://127.0.0.1:3000/login > /dev/null || exit 1

# server.js es el servidor de producción que genera `next build` (equivale a `next start`)
CMD ["node", "server.js"]
