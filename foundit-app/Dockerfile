# ---------- build stage: compile the Vue SPA ----------
FROM node:20-alpine AS build
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

# Which API the built app calls. For the self-contained Docker stack this is the
# local "api" container published on the host at :8081 (NOT the AlwaysData URL in
# .env.production). Written to .env.production.local, which outranks .env.production
# in Vite's env precedence, guaranteeing the local URL is baked in.
ARG VITE_API_BASE=http://localhost:8081/api
RUN echo "VITE_API_BASE=$VITE_API_BASE" > .env.production.local

RUN npm run build

# ---------- serve stage: static files via nginx ----------
FROM nginx:1.27-alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
