-- Campos que la vitrina necesita y que el modelo Product no tenía.
ALTER TABLE "products" ADD COLUMN "slug" TEXT NOT NULL DEFAULT '';
ALTER TABLE "products" ADD COLUMN "brand" TEXT NOT NULL DEFAULT '';
ALTER TABLE "products" ADD COLUMN "presentation" TEXT NOT NULL DEFAULT '';
ALTER TABLE "products" ADD COLUMN "description" TEXT NOT NULL DEFAULT '';
ALTER TABLE "products" ADD COLUMN "includes" TEXT NOT NULL DEFAULT '[]';
ALTER TABLE "products" ADD COLUMN "label" TEXT NOT NULL DEFAULT '';
