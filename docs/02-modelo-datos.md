# 02 · Modelo de datos

Motor: MySQL, `utf8mb4`, InnoDB. Dinero en **céntimos** (`INT UNSIGNED`).

**En Laravel el esquema se define con migraciones** (`backend/database/migrations`), no con un
`schema.sql` a mano. El DDL de abajo es una **referencia lógica** para diseñar las migraciones;
si difiere de las migraciones, mandan las migraciones. Adaptaciones al hacerlas:

- Convenciones Laravel: `id` bigint autoincremental (`$table->id()`), `timestamps()` (`created_at`/`updated_at`)
  en todas las tablas salvo `events` (solo `occurred_at`) y `order_status_history` (solo `changed_at`).
- Los `ENUM` se guardan como `string` y se validan con **PHP backed enums** (`app/Enums`), más
  portable y fácil de cambiar que el ENUM de MySQL.
- `events.payload`: `$table->json('payload')` con cast `array` en el modelo.
- `users` ya existe en Laravel; ampliarla con `role` en vez de crear otra tabla. La migración de
  `sessions` (si `SESSION_DRIVER=database`) y `personal_access_tokens` (Sanctum) vienen del framework.
- Tablas `cache`/`jobs` del esqueleto: se pueden eliminar si no se usan (menos ruido).

## Entidades

| Entidad | Tipo de dato | Notas |
|---|---|---|
| `users` | maestro | clientes y admin **de prueba** |
| `categories` | maestro | |
| `products` | maestro | ≥ 8, con stock, precio, descripción, imagen |
| `coupons` | maestro | descuentos simulados |
| `orders` | transaccional | `public_id` único y legible; snapshot de importes y dirección |
| `order_items` | transaccional | snapshot de nombre y precio unitario |
| `order_status_history` | transaccional | trazabilidad de cambios de estado |
| `payments` | transaccional | pago **simulado**, solo `last4` |
| `support_tickets` | transaccional | incidencias / contacto postventa |
| `events` | instrumentación | log de eventos de negocio |

## Relaciones

```
categories 1─N products
users 1─N orders 1─N order_items N─1 products
orders 1─N payments · orders 1─N order_status_history · orders 0..1─1 coupons
users 1─N support_tickets · orders 0..1─N support_tickets
events: referencia lógica (entity_type + entity_id), sin FK estricta para no bloquear el log
```

## DDL de referencia

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,          -- ficticio: @example.test
  name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  sku VARCHAR(40) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  short_description VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  price_cents INT UNSIGNED NOT NULL,           -- precio SIN IVA
  tax_rate_bp SMALLINT UNSIGNED NOT NULL DEFAULT 2100, -- puntos básicos: 2100 = 21 %
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  percent_off TINYINT UNSIGNED NOT NULL,
  min_subtotal_cents INT UNSIGNED NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  expires_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(24) NOT NULL UNIQUE,       -- p.ej. ORD-20261004-A3F9K2 (no secuencial adivinable)
  user_id INT UNSIGNED NOT NULL,
  status ENUM('created','paid_simulated','pending_preparation','shipped','cancelled','incident') NOT NULL DEFAULT 'created',
  coupon_id INT UNSIGNED NULL,
  subtotal_cents INT UNSIGNED NOT NULL,        -- base imponible de líneas
  discount_cents INT UNSIGNED NOT NULL DEFAULT 0,
  shipping_cents INT UNSIGNED NOT NULL DEFAULT 0,
  tax_cents INT UNSIGNED NOT NULL,
  total_cents INT UNSIGNED NOT NULL,
  ship_name VARCHAR(120) NOT NULL,
  ship_address VARCHAR(255) NOT NULL,
  ship_city VARCHAR(80) NOT NULL,
  ship_postal_code VARCHAR(10) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (coupon_id) REFERENCES coupons(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(160) NOT NULL,          -- snapshot
  unit_price_cents INT UNSIGNED NOT NULL,      -- snapshot
  tax_rate_bp SMALLINT UNSIGNED NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(30) NULL,
  to_status VARCHAR(30) NOT NULL,
  changed_by INT UNSIGNED NULL,                -- users.id o NULL si es el sistema
  note VARCHAR(255) NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  method ENUM('test_card','bank_transfer_sim') NOT NULL,
  status ENUM('approved','declined') NOT NULL,
  amount_cents INT UNSIGNED NOT NULL,
  card_last4 CHAR(4) NULL,                     -- NUNCA guardar el número completo ni el CVV
  transaction_ref VARCHAR(40) NOT NULL UNIQUE, -- ficticio: SIM-xxxxxxxx
  failure_reason VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB;

CREATE TABLE support_tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(24) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  type ENUM('support_request','incident') NOT NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB;

CREATE TABLE events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id CHAR(36) NOT NULL UNIQUE,           -- UUID v4
  event_type VARCHAR(60) NOT NULL,
  schema_version TINYINT UNSIGNED NOT NULL DEFAULT 1,
  occurred_at DATETIME(3) NOT NULL,            -- UTC
  session_id VARCHAR(64) NULL,
  user_id INT UNSIGNED NULL,
  entity_type VARCHAR(30) NULL,
  entity_id VARCHAR(40) NULL,
  source ENUM('frontend','backend') NOT NULL,
  payload JSON NOT NULL,
  INDEX idx_type_time (event_type, occurred_at),
  INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;
```

> Si el hosting resulta ser MariaDB, `JSON` es alias de `LONGTEXT`; funciona igual para este uso. Verificar al importar.

## Datos de prueba (Seeders y Factories)

Se generan con `database/seeders` (+ factories donde ayuden) mediante `php artisan migrate --seed`.
Para el hosting sin SSH, se exporta un volcado con `mysqldump` de la BBDD local ya sembrada (ver `01-arquitectura.md`).

- **Empresa ficticia: Piquantum** (tienda de teclados y accesorios; confirmar el sector con el grupo). Mantener datos coherentes
  con el caso (categorías como teclados mecánicos, switches/keycaps, accesorios…) y reflejarlo en el README.
- 3-4 categorías, **10-12 productos** (mínimo 8) con nombre, descripción realista, precio,
  stock variado (incluir 1 producto con stock 0 y otro con poco stock para probar reglas).
- Usuarios: 1 admin y 2-3 clientes, emails `@example.test`, nombres inventados.
  Contraseñas de prueba **documentadas en el README** (son ficticias; en BBDD solo el hash).
- 2 cupones (uno válido, uno caducado) y ~6 pedidos históricos en estados variados con sus
  pagos, líneas, historial de estados y eventos, para que el back-office no esté vacío.
- Imágenes: propias, de dominio público o generadas; revisar licencia y anotarla en el README.
