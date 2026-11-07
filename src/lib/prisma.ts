import path from "node:path";

import { Prisma, PrismaClient } from "@prisma/client";

/**
 * Prisma resuelve las rutas relativas de SQLite desde la carpeta del esquema.
 * Dentro del bundle de Next eso apunta a .next/server, donde la base no
 * existe, y el login falla con "Unable to open the database file".
 * Se normaliza a ruta absoluta antes de crear el cliente.
 */
function normalizarRutaSqlite() {
  const url = process.env.DATABASE_URL;
  if (!url || !url.startsWith("file:./")) return;
  const rel = url.slice("file:".length).replace(/^\.\//, "");
  const destino = rel.startsWith("prisma/") ? rel : path.join("prisma", rel);
  process.env.DATABASE_URL = `file:${path.resolve(process.cwd(), destino)}`;
}

normalizarRutaSqlite();

const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined;
  prismaInit: Promise<void> | undefined;
  prismaMiddlewareRegistered: boolean | undefined;
};

function createPrismaClient() {
  return new PrismaClient({
    log: process.env.NODE_ENV === "development" ? ["warn", "error"] : ["error"],
  });
}

export const prisma = globalForPrisma.prisma ?? createPrismaClient();
globalForPrisma.prisma = prisma;

function isInitializationPragma(params: Prisma.MiddlewareParams) {
  if (params.action !== "executeRaw" && params.action !== "queryRaw") return false;
  const query = Array.isArray(params.args) ? params.args[0] : undefined;
  return typeof query === "string" && query.trimStart().toUpperCase().startsWith("PRAGMA ");
}

export function initializePrisma(): Promise<void> {
  if (!globalForPrisma.prismaInit) {
    globalForPrisma.prismaInit = (async () => {
      await prisma.$queryRawUnsafe("PRAGMA journal_mode = WAL");
      await prisma.$queryRawUnsafe("PRAGMA synchronous = NORMAL");
      await prisma.$queryRawUnsafe("PRAGMA foreign_keys = ON");
      await prisma.$queryRawUnsafe("PRAGMA busy_timeout = 5000");
    })();
  }

  return globalForPrisma.prismaInit;
}

if (!globalForPrisma.prismaMiddlewareRegistered) {
  prisma.$use(async (params, next) => {
    if (!isInitializationPragma(params)) await initializePrisma();
    return next(params);
  });
  globalForPrisma.prismaMiddlewareRegistered = true;
}
