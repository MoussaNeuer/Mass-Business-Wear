-- ============================================================
--  MASS BUSINESS WEAR — commandes de démonstration (stats + clients)
--  Import après seed_dev.sql : mysql -u root mbw < database/seed_orders_demo.sql
-- ============================================================
USE mbw;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE order_status_history;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE customers;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO customers (id, name, phone, whatsapp, created_at) VALUES
(1,'Aminata Diallo','771234567','221771234567', DATE_SUB(NOW(), INTERVAL 40 DAY)),
(2,'Moussa Ba','769876543','221769876543', DATE_SUB(NOW(), INTERVAL 35 DAY)),
(3,'Fatou Ndiaye','781112233',NULL, DATE_SUB(NOW(), INTERVAL 28 DAY)),
(4,'Ibrahima Sarr','775556677','221775556677', DATE_SUB(NOW(), INTERVAL 21 DAY)),
(5,'Awa Diop','704445566',NULL, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(6,'Cheikh Gueye','762223344',NULL, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(7,'Mariama Kane','789998877',NULL, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- unit_price = prix réel au moment de la démo (promo incluses)
INSERT INTO orders (id, reference, idempotency_key, customer_id, address, zone, subtotal, shipping, total, status, notes, source, created_at) VALUES
(1,'MBW-DEMO-001',NULL,1,'Sacré-Cœur 3, Dakar','Dakar centre',13000,2000,15000,'DELIVERED',NULL,'web', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(2,'MBW-DEMO-002',NULL,2,'Parcelles Assainies U15','Banlieue',25000,3000,28000,'DELIVERED',NULL,'web', DATE_SUB(NOW(), INTERVAL 33 DAY)),
(3,'MBW-DEMO-003',NULL,3,'Plateau, Avenue Bourguiba','Dakar centre',15000,2000,17000,'DELIVERED','Livré avant midi','whatsapp', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(4,'MBW-DEMO-004',NULL,1,'Sacré-Cœur 3, Dakar','Dakar centre',30000,2000,32000,'DELIVERED',NULL,'web', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(5,'MBW-DEMO-005',NULL,4,'Grand Yoff','Banlieue',45000,3000,48000,'DELIVERED',NULL,'web', DATE_SUB(NOW(), INTERVAL 19 DAY)),
(6,'MBW-DEMO-006',NULL,5,'Ouakam','Banlieue',12000,3000,15000,'CANCELLED','Annulé client','web', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(7,'MBW-DEMO-007',NULL,6,'Thiès centre','Intérieur',16000,5000,21000,'DELIVERED',NULL,'whatsapp', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(8,'MBW-DEMO-008',NULL,2,'Parcelles Assainies U15','Banlieue',8000,3000,11000,'DELIVERED',NULL,'web', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(9,'MBW-DEMO-009',NULL,7,'Mermoz','Dakar centre',19000,2000,21000,'DELIVERED',NULL,'web', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(10,'MBW-DEMO-010',NULL,3,'Plateau, Avenue Bourguiba','Dakar centre',37500,2000,39500,'PROCESSING','Appeler avant passage','whatsapp', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(11,'MBW-DEMO-011',NULL,1,'Sacré-Cœur 3, Dakar','Dakar centre',12000,2000,14000,'CONFIRMED',NULL,'web', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(12,'MBW-DEMO-012',NULL,5,'Ouakam','Banlieue',15000,3000,18000,'NEW',NULL,'web', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, unit_price, quantity) VALUES
(1,1,2,'T-shirt Premium Noir','M','Noir',8000,1),
(1,11,38,'Casquette MBW Signature','Unique','Noir',5000,1),
(2,5,19,'Ensemble Business Buro','L','Noir',25000,1),
(3,3,10,'Chemise Blanche Classique','M','Blanc',15000,1),
(4,6,23,'Ensemble Streetwear Duo','M','Gris',26000,1),
(5,7,26,'Ensemble Costard Premium','50','Noir',45000,1),
(6,9,32,'Pantalon Cargo Noir','M','Noir',16000,1),
(7,9,31,'Pantalon Cargo Noir','S','Noir',16000,1),
(8,1,1,'T-shirt Premium Noir','S','Noir',8000,1),
(9,4,13,'Sweat à Capuche MBW','L','Noir',15000,1),
(9,12,39,'Bonnet Hiver Noir','Unique','Noir',4000,1),
(10,5,18,'Ensemble Business Buro','M','Noir',25000,1),
(10,10,36,'Jean Slim Bleu','40','Bleu',12500,1),
(11,2,5,'Polo Signature Rouge','M','Rouge',12000,1),
(12,4,12,'Sweat à Capuche MBW','M','Noir',15000,1);

INSERT INTO order_status_history (order_id, from_status, to_status, admin_id, note, created_at) VALUES
(1,NULL,'NEW',NULL,'Commande créée depuis la boutique', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(1,'NEW','DELIVERED',1,'Démo seed', DATE_SUB(NOW(), INTERVAL 37 DAY)),
(10,NULL,'NEW',NULL,'Commande créée depuis la boutique', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(10,'NEW','PROCESSING',1,'En préparation', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(12,NULL,'NEW',NULL,'Commande créée depuis la boutique', DATE_SUB(NOW(), INTERVAL 1 DAY));
