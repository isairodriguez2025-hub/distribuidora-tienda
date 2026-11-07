-- El slug identifica al producto en la URL: no puede repetirse.
-- Va en una migración aparte para que en una base nueva (vacía) se cree sin
-- problema, y en una con datos se aplique después del backfill.
CREATE UNIQUE INDEX "products_slug_key" ON "products"("slug");
