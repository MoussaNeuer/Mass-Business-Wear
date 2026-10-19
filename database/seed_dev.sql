-- ============================================================
--  MASS BUSINESS WEAR — données de développement
--  Import : mysql -u root mbw < database/seed_dev.sql
--  (réinitialise catalogue et commandes, conserve admins/settings)
-- ============================================================
USE mbw;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE order_status_history;
TRUNCATE TABLE order_items;
TRUNCATE TABLE stock_movements;
TRUNCATE TABLE orders;
TRUNCATE TABLE customers;
TRUNCATE TABLE product_images;
TRUNCATE TABLE product_variants;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO categories (id, name, slug, description, sort_order, is_active) VALUES
(1,'Vêtements','vetements','T-shirts, polos, chemises et sweats premium.',1,1),
(2,'Ensembles','ensembles','Looks complets coordonnés, business et streetwear.',2,1),
(3,'Pantalons','pantalons','Chinos, cargos et jeans coupés pour durer.',3,1),
(4,'Accessoires','accessoires','Casquettes, bonnets, sacs et détails qui font la différence.',4,1);

INSERT INTO products (id, category_id, name, slug, description, price, promo_price, status, is_featured, sold_count, created_at) VALUES
(1,1,'T-shirt Premium Noir','t-shirt-premium-noir','T-shirt coton lourd 220g, coupe régulière, impression MBW discrète au dos. Lavable en machine sans perdre sa forme.',8000,NULL,'active',1,24,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2,1,'Polo Signature Rouge','polo-signature-rouge','Polo piqué premium, col structuré, broderie MASS rouge sur poitrine. L''élégance streetwear au bureau.',12000,NULL,'active',1,18,DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3,1,'Chemise Blanche Classique','chemise-blanche-classique','Chemise popeline blanche, coupe ajustée, boutons nacrés. Le basique indispensable du dressing business.',15000,NULL,'active',0,9,DATE_SUB(NOW(), INTERVAL 10 DAY)),
(4,1,'Sweat à Capuche MBW','sweat-capuche-mbw','Sweat capuchon molleton 320g, poche kangourou, cordons contrastés rouge. Chaud, solide, iconique.',18000,15000,'active',1,31,DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5,2,'Ensemble Business Buro','ensemble-business-buro','Veste structurée + pantalon droit assortis. Tissu respirant, idéal journées longues et rendez-vous clients.',25000,NULL,'active',1,12,DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6,2,'Ensemble Streetwear Duo','ensemble-streetwear-duo','Hoodie oversize + cargo assortis. Coupe unisexe, finitions renforcées sur les points d''usure.',30000,26000,'active',0,8,DATE_SUB(NOW(), INTERVAL 12 DAY)),
(7,2,'Ensemble Costard Premium','ensemble-costard-premium','Costard trois pièces premium, doublure satinée, retouche offerte sur demande. Pour les grandes occasions.',45000,NULL,'active',0,4,DATE_SUB(NOW(), INTERVAL 20 DAY)),
(8,3,'Pantalon Chino Beige','pantalon-chino-beige','Chino coton-stretch, coloris sable, taille élastiquée discrète. Se porte chaussures ou sneakers.',14000,NULL,'active',0,15,DATE_SUB(NOW(), INTERVAL 9 DAY)),
(9,3,'Pantalon Cargo Noir','pantalon-cargo-noir','Cargo six poches, tissu ripstop, ajustement possible à la cheville. Pratique et tendance.',16000,NULL,'active',1,21,DATE_SUB(NOW(), INTERVAL 4 DAY)),
(10,3,'Jean Slim Bleu','jean-slim-bleu','Jean slim délavé bleu moyen, stretch confort, coutures renforcées. Un classique qui ne lâche pas.',15000,12500,'active',0,6,DATE_SUB(NOW(), INTERVAL 15 DAY)),
(11,4,'Casquette MBW Signature','casquette-mbw-signature','Casquette ajustable, broderie MBW rouge, visière préformée. Protection soleil + style garanti.',5000,NULL,'active',1,40,DATE_SUB(NOW(), INTERVAL 6 DAY)),
(12,4,'Bonnet Hiver Noir','bonnet-hiver-noir','Bonnet côtelé doublé, broderie ton sur ton. Chaleur maximale pour les matins dakarois.',4000,NULL,'active',0,2,DATE_SUB(NOW(), INTERVAL 1 DAY)),
(13,4,'Sac à Dos Urbain','sac-a-dos-urbain','Sac à dos 22L, compartiment ordinateur 15", zip anti-vol. Résistant aux averses.',12000,NULL,'active',0,0,DATE_SUB(NOW(), INTERVAL 30 DAY)),
(14,1,'T-shirt Blanc Essentiel','t-shirt-blanc-essentiel','Brouillon : variante blanche du t-shirt premium. Non publié.',7000,NULL,'draft',0,0,DATE_SUB(NOW(), INTERVAL 2 DAY)),
(15,3,'Pantalon Lin Été','pantalon-lin-ete','Archivé : collection été passée.',17000,NULL,'archived',0,0,DATE_SUB(NOW(), INTERVAL 60 DAY));

INSERT INTO product_variants (product_id, size, color, stock, is_active) VALUES
(1,'S','Noir',12,1),(1,'M','Noir',20,1),(1,'L','Noir',18,1),(1,'XL','Noir',8,1),
(2,'M','Rouge',10,1),(2,'L','Rouge',14,1),(2,'M','Blanc',6,1),(2,'L','Blanc',9,1),
(3,'S','Blanc',7,1),(3,'M','Blanc',11,1),(3,'L','Blanc',5,1),
(4,'M','Noir',15,1),(4,'L','Noir',22,1),(4,'XL','Noir',10,1),(4,'M','Gris',8,1),(4,'L','Gris',12,1),(4,'XL','Gris',0,1),
(5,'M','Noir',6,1),(5,'L','Noir',9,1),(5,'M','Bleu',4,1),(5,'L','Bleu',7,1),
(6,'S','Gris',5,1),(6,'M','Gris',8,1),(6,'L','Gris',6,1),
(7,'48','Noir',2,1),(7,'50','Noir',3,1),(7,'52','Noir',1,1),
(8,'38','Beige',9,1),(8,'40','Beige',12,1),(8,'42','Beige',7,1),
(9,'S','Noir',6,1),(9,'M','Noir',10,1),(9,'L','Noir',0,1),(9,'XL','Noir',4,1),
(10,'38','Bleu',5,1),(10,'40','Bleu',8,1),(10,'42','Bleu',3,1),
(11,'Unique','Noir',25,1),
(12,'Unique','Noir',0,1),
(13,'Unique','Noir',2,1);

INSERT INTO product_images (product_id, file, sort_order) VALUES
(1,'p1-1.jpg',0),(1,'p1-2.jpg',1),
(2,'p2-1.jpg',0),(2,'p2-2.jpg',1),
(3,'p3-1.jpg',0),(3,'p3-2.jpg',1),
(4,'p4-1.jpg',0),(4,'p4-2.jpg',1),
(5,'p5-1.jpg',0),(5,'p5-2.jpg',1),
(6,'p6-1.jpg',0),(6,'p6-2.jpg',1),
(7,'p7-1.jpg',0),(7,'p7-2.jpg',1),
(8,'p8-1.jpg',0),(8,'p8-2.jpg',1),
(9,'p9-1.jpg',0),(9,'p9-2.jpg',1),
(10,'p10-1.jpg',0),(10,'p10-2.jpg',1),
(11,'p11-1.jpg',0),(11,'p11-2.jpg',1),
(12,'p12-1.jpg',0),(12,'p12-2.jpg',1),
(13,'p13-1.jpg',0),(13,'p13-2.jpg',1);
