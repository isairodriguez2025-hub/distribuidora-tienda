-- Tablas de la tienda en línea (capítulo 5 del documento) que se habían
-- aplicado con db push y no tenían migración: direcciones, pedidos,
-- envíos, repartidores y ubicaciones. Incluye también los modelos
-- Notification y StockBatch que se perdieron en el merge.

-- AlterTable
ALTER TABLE "suppliers" ADD COLUMN "password_reset_expires" DATETIME;
ALTER TABLE "suppliers" ADD COLUMN "password_reset_token" TEXT;
ALTER TABLE "suppliers" ADD COLUMN "recovery_email" TEXT;

-- AlterTable
ALTER TABLE "users" ADD COLUMN "password_reset_expires" DATETIME;
ALTER TABLE "users" ADD COLUMN "password_reset_token" TEXT;
ALTER TABLE "users" ADD COLUMN "recovery_email" TEXT;

-- CreateTable
CREATE TABLE "stock_batches" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "product_id" INTEGER NOT NULL,
    "quantity" INTEGER NOT NULL,
    "expiry_date" DATETIME,
    "batch_code" TEXT NOT NULL DEFAULT '',
    "received_via" TEXT NOT NULL DEFAULT 'manual',
    "notes" TEXT NOT NULL DEFAULT '',
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "stock_batches_product_id_fkey" FOREIGN KEY ("product_id") REFERENCES "products" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- CreateTable
CREATE TABLE "notifications" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "type" TEXT NOT NULL,
    "title" TEXT NOT NULL,
    "message" TEXT NOT NULL,
    "product_id" INTEGER,
    "severity" TEXT NOT NULL DEFAULT 'warning',
    "read" BOOLEAN NOT NULL DEFAULT false,
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "notifications_product_id_fkey" FOREIGN KEY ("product_id") REFERENCES "products" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

-- CreateTable
CREATE TABLE "addresses" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "customer_id" INTEGER NOT NULL,
    "alias" TEXT NOT NULL DEFAULT 'Casa',
    "street" TEXT NOT NULL,
    "number" TEXT NOT NULL DEFAULT '',
    "colony" TEXT NOT NULL DEFAULT '',
    "city" TEXT NOT NULL DEFAULT '',
    "state" TEXT NOT NULL DEFAULT '',
    "zip" TEXT NOT NULL DEFAULT '',
    "references" TEXT NOT NULL DEFAULT '',
    "lat" REAL,
    "lng" REAL,
    "is_primary" BOOLEAN NOT NULL DEFAULT false,
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "addresses_customer_id_fkey" FOREIGN KEY ("customer_id") REFERENCES "customers" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- CreateTable
CREATE TABLE "store_orders" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "folio" TEXT NOT NULL,
    "customer_id" INTEGER,
    "status" TEXT NOT NULL DEFAULT 'nuevo',
    "subtotal" REAL NOT NULL DEFAULT 0,
    "shipping" REAL NOT NULL DEFAULT 0,
    "total" REAL NOT NULL DEFAULT 0,
    "payment_method" TEXT NOT NULL DEFAULT 'transferencia',
    "address_id" INTEGER,
    "address_text" TEXT NOT NULL DEFAULT '',
    "notes" TEXT NOT NULL DEFAULT '',
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" DATETIME NOT NULL,
    CONSTRAINT "store_orders_customer_id_fkey" FOREIGN KEY ("customer_id") REFERENCES "customers" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT "store_orders_address_id_fkey" FOREIGN KEY ("address_id") REFERENCES "addresses" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

-- CreateTable
CREATE TABLE "store_order_items" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "order_id" INTEGER NOT NULL,
    "product_id" INTEGER,
    "product_name" TEXT NOT NULL,
    "unit_price" REAL NOT NULL,
    "quantity" INTEGER NOT NULL,
    "subtotal" REAL NOT NULL,
    CONSTRAINT "store_order_items_order_id_fkey" FOREIGN KEY ("order_id") REFERENCES "store_orders" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT "store_order_items_product_id_fkey" FOREIGN KEY ("product_id") REFERENCES "products" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

-- CreateTable
CREATE TABLE "drivers" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "name" TEXT NOT NULL,
    "phone" TEXT NOT NULL DEFAULT '',
    "vehicle" TEXT NOT NULL DEFAULT '',
    "plate" TEXT NOT NULL DEFAULT '',
    "active" BOOLEAN NOT NULL DEFAULT true,
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- CreateTable
CREATE TABLE "deliveries" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "order_id" INTEGER NOT NULL,
    "driver_id" INTEGER,
    "status" TEXT NOT NULL DEFAULT 'asignado',
    "started_at" DATETIME,
    "delivered_at" DATETIME,
    "proof" TEXT NOT NULL DEFAULT '',
    "notes" TEXT NOT NULL DEFAULT '',
    CONSTRAINT "deliveries_order_id_fkey" FOREIGN KEY ("order_id") REFERENCES "store_orders" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT "deliveries_driver_id_fkey" FOREIGN KEY ("driver_id") REFERENCES "drivers" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

-- CreateTable
CREATE TABLE "driver_locations" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "driver_id" INTEGER NOT NULL,
    "delivery_id" INTEGER,
    "lat" REAL NOT NULL,
    "lng" REAL NOT NULL,
    "accuracy_m" REAL,
    "recorded_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "driver_locations_driver_id_fkey" FOREIGN KEY ("driver_id") REFERENCES "drivers" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT "driver_locations_delivery_id_fkey" FOREIGN KEY ("delivery_id") REFERENCES "deliveries" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- RedefineTables
PRAGMA defer_foreign_keys=ON;
PRAGMA foreign_keys=OFF;
CREATE TABLE "new_stock_alerts" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "sale_id" INTEGER NOT NULL,
    "product_id" INTEGER NOT NULL,
    "product_name" TEXT NOT NULL,
    "quantity_sold" INTEGER NOT NULL,
    "stock_before" INTEGER NOT NULL,
    "stock_after" INTEGER NOT NULL,
    "shortage" INTEGER NOT NULL DEFAULT 0,
    "status" TEXT NOT NULL DEFAULT 'pending',
    "acked_by" INTEGER,
    "acked_at" DATETIME,
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "stock_alerts_sale_id_fkey" FOREIGN KEY ("sale_id") REFERENCES "sales" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT "stock_alerts_product_id_fkey" FOREIGN KEY ("product_id") REFERENCES "products" ("id") ON DELETE RESTRICT ON UPDATE CASCADE
);
INSERT INTO "new_stock_alerts" ("acked_at", "acked_by", "created_at", "id", "product_id", "product_name", "quantity_sold", "sale_id", "shortage", "status", "stock_after", "stock_before") SELECT "acked_at", "acked_by", "created_at", "id", "product_id", "product_name", "quantity_sold", "sale_id", "shortage", "status", "stock_after", "stock_before" FROM "stock_alerts";
DROP TABLE "stock_alerts";
ALTER TABLE "new_stock_alerts" RENAME TO "stock_alerts";
CREATE INDEX "stock_alerts_status_idx" ON "stock_alerts"("status");
CREATE INDEX "stock_alerts_sale_id_idx" ON "stock_alerts"("sale_id");
CREATE INDEX "stock_alerts_product_id_idx" ON "stock_alerts"("product_id");
CREATE TABLE "new_supplier_orders" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "supplier_id" INTEGER NOT NULL,
    "status" TEXT NOT NULL DEFAULT 'pending',
    "notes" TEXT NOT NULL DEFAULT '',
    "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" DATETIME NOT NULL,
    "sent_at" DATETIME,
    "created_by_id" INTEGER,
    "received_by_id" INTEGER,
    CONSTRAINT "supplier_orders_supplier_id_fkey" FOREIGN KEY ("supplier_id") REFERENCES "suppliers" ("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT "supplier_orders_created_by_id_fkey" FOREIGN KEY ("created_by_id") REFERENCES "users" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT "supplier_orders_received_by_id_fkey" FOREIGN KEY ("received_by_id") REFERENCES "users" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);
INSERT INTO "new_supplier_orders" ("created_at", "created_by_id", "id", "notes", "received_by_id", "sent_at", "status", "supplier_id", "updated_at") SELECT "created_at", "created_by_id", "id", "notes", "received_by_id", "sent_at", "status", "supplier_id", "updated_at" FROM "supplier_orders";
DROP TABLE "supplier_orders";
ALTER TABLE "new_supplier_orders" RENAME TO "supplier_orders";
CREATE INDEX "supplier_orders_supplier_id_idx" ON "supplier_orders"("supplier_id");
CREATE INDEX "supplier_orders_status_idx" ON "supplier_orders"("status");
CREATE INDEX "supplier_orders_created_by_id_idx" ON "supplier_orders"("created_by_id");
CREATE INDEX "supplier_orders_received_by_id_idx" ON "supplier_orders"("received_by_id");
CREATE TABLE "new_tax_rules" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "name" TEXT NOT NULL DEFAULT '',
    "percentage" REAL NOT NULL,
    "apply_time" TEXT NOT NULL DEFAULT '20:00',
    "end_time" TEXT NOT NULL DEFAULT '',
    "scope" TEXT NOT NULL DEFAULT 'ALL',
    "scope_value" INTEGER,
    "active" BOOLEAN NOT NULL DEFAULT true,
    "status" TEXT NOT NULL DEFAULT 'schedule',
    "updated_at" DATETIME NOT NULL
);
INSERT INTO "new_tax_rules" ("active", "apply_time", "end_time", "id", "name", "percentage", "scope", "scope_value", "status", "updated_at") SELECT "active", "apply_time", "end_time", "id", "name", "percentage", "scope", "scope_value", "status", "updated_at" FROM "tax_rules";
DROP TABLE "tax_rules";
ALTER TABLE "new_tax_rules" RENAME TO "tax_rules";
PRAGMA foreign_keys=ON;
PRAGMA defer_foreign_keys=OFF;

-- CreateIndex
CREATE INDEX "stock_batches_product_id_expiry_date_idx" ON "stock_batches"("product_id", "expiry_date");

-- CreateIndex
CREATE INDEX "notifications_read_created_at_idx" ON "notifications"("read", "created_at");

-- CreateIndex
CREATE INDEX "notifications_product_id_idx" ON "notifications"("product_id");

-- CreateIndex
CREATE INDEX "addresses_customer_id_idx" ON "addresses"("customer_id");

-- CreateIndex
CREATE UNIQUE INDEX "store_orders_folio_key" ON "store_orders"("folio");

-- CreateIndex
CREATE INDEX "store_orders_customer_id_idx" ON "store_orders"("customer_id");

-- CreateIndex
CREATE INDEX "store_orders_status_idx" ON "store_orders"("status");

-- CreateIndex
CREATE INDEX "store_orders_created_at_idx" ON "store_orders"("created_at");

-- CreateIndex
CREATE INDEX "store_order_items_order_id_idx" ON "store_order_items"("order_id");

-- CreateIndex
CREATE UNIQUE INDEX "deliveries_order_id_key" ON "deliveries"("order_id");

-- CreateIndex
CREATE INDEX "deliveries_driver_id_idx" ON "deliveries"("driver_id");

-- CreateIndex
CREATE INDEX "driver_locations_delivery_id_idx" ON "driver_locations"("delivery_id");

-- CreateIndex
CREATE INDEX "driver_locations_driver_id_recorded_at_idx" ON "driver_locations"("driver_id", "recorded_at");

-- CreateIndex
CREATE UNIQUE INDEX "suppliers_password_reset_token_key" ON "suppliers"("password_reset_token");

-- CreateIndex
CREATE UNIQUE INDEX "users_password_reset_token_key" ON "users"("password_reset_token");

