#!/usr/bin/env node
/**
 * Proxy de prefijo para publicar el sitio detrás de Tailscale Funnel.
 *
 * Tailscale Funnel elimina el prefijo del mount (/cecyt3-lab) antes de
 * reenviar la petición, pero la aplicación Next.js está construida con
 * basePath=/cecyt3-lab. Este proxy vuelve a anteponer el prefijo.
 *
 *   Funnel /cecyt3-lab  ->  este proxy (:3021)  ->  Next.js (:3020)
 */
import http from "node:http";

const PREFIX = process.env.PREFIX ?? "/cecyt3-lab";
const TARGET_HOST = process.env.TARGET_HOST ?? "127.0.0.1";
const TARGET_PORT = Number(process.env.TARGET_PORT ?? 3020);
const LISTEN_PORT = Number(process.env.LISTEN_PORT ?? 3021);

const server = http.createServer((req, res) => {
  const original = req.url ?? "/";
  const suffix = original === "/" ? "" : original;
  const path = original.startsWith(PREFIX) ? original : PREFIX + suffix;

  if (process.env.PREFIX_PROXY_LOG) console.log(`${req.method} ${original} -> ${path}`);
  const headers = { ...req.headers, host: `${TARGET_HOST}:${TARGET_PORT}` };
  delete headers["accept-encoding"];

  const upstream = http.request(
    { host: TARGET_HOST, port: TARGET_PORT, method: req.method, path, headers },
    (up) => {
      const outHeaders = { ...up.headers };
      // Next redirige a rutas absolutas con basePath; se dejan tal cual.
      res.writeHead(up.statusCode ?? 502, outHeaders);
      up.pipe(res);
    },
  );

  upstream.on("error", (err) => {
    res.writeHead(502, { "content-type": "text/plain; charset=utf-8" });
    res.end(`Proxy error: ${err.message}`);
  });

  req.pipe(upstream);
});

server.listen(LISTEN_PORT, "127.0.0.1", () => {
  console.log(`prefix proxy ${PREFIX} -> http://${TARGET_HOST}:${TARGET_PORT} on :${LISTEN_PORT}`);
});
