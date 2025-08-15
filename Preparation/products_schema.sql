-- -----------------------------------------------------
-- Table `mydb`.`products`
-- -----------------------------------------------------
# 商品マスター
CREATE TABLE IF NOT EXISTS `mydb`.`products` (
  `id` DECIMAL NOT NULL,
  `code` VARCHAR(45) NOT NULL, # POS商品ID
  `name` VARCHAR(45) NOT NULL, # 商品名
  `description` VARCHAR(256) NOT NULL, # 商品説明
  `price` FLOAT NOT NULL, # 価格
  `tax_in_price` FLOAT NOT NULL, # 税込価格
  `cost` FLOAT NOT NULL, # 原価
  `tax_type` INT NOT NULL, # 税区分
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`));


-- -----------------------------------------------------
-- Table `mydb`.`categories`
-- -----------------------------------------------------
# 商品カテゴリマスター
CREATE TABLE IF NOT EXISTS `mydb`.`categories` (
  `id` DECIMAL NOT NULL, # カテゴリID
  `name` VARCHAR(255) NOT NULL, # カテゴリ名
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `sort_order` INT NOT NULL, # ソート順
  PRIMARY KEY (`id`));


-- -----------------------------------------------------
-- Table `mydb`.`category_product`
-- -----------------------------------------------------
# 商品カテゴリ紐付け
CREATE TABLE IF NOT EXISTS `mydb`.`category_product` (
  `id` DECIMAL NOT NULL, # 紐付けID
  `product_id` DECIMAL NOT NULL, # 商品ID
  `category_id` DECIMAL NOT NULL, # カテゴリID
  `sort_order` INT NOT NULL, # ソート順
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id_idx` (`product_id` ASC) VISIBLE,
  INDEX `category_id_idx` (`category_id` ASC) VISIBLE,
  CONSTRAINT `product_id`
    FOREIGN KEY (`product_id`)
    REFERENCES `mydb`.`products` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `category_id`
    FOREIGN KEY (`category_id`)
    REFERENCES `mydb`.`categories` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `mydb`.`options`
-- -----------------------------------------------------
# 商品オプションマスター
CREATE TABLE IF NOT EXISTS `mydb`.`options` (
  `id` DECIMAL NOT NULL, # オプションID
  `title` VARCHAR(45) NOT NULL, # オプションタイトル
  `description` VARCHAR(45) NOT NULL, # オプション説明
  `required` TINYINT NOT NULL, # 必須フラグ
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`));


-- -----------------------------------------------------
-- Table `mydb`.`option_detail`
-- -----------------------------------------------------
# 商品オプション詳細
CREATE TABLE IF NOT EXISTS `mydb`.`option_detail` (
  `id` DECIMAL NOT NULL, # オプション詳細ID
  `option_id` DECIMAL NOT NULL, # オプションID
  `product_id` DECIMAL NOT NULL, # 商品ID
  `default` TINYINT NOT NULL, # デフォルトフラグ(画面表示時に選択される)
  `sort_order` INT NOT NULL, # ソート順
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id_idx` (`product_id` ASC) VISIBLE,
  INDEX `option_id_idx` (`option_id` ASC) VISIBLE,
  CONSTRAINT `option_id`
    FOREIGN KEY (`option_id`)
    REFERENCES `mydb`.`options` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `product_id`
    FOREIGN KEY (`product_id`)
    REFERENCES `mydb`.`products` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `mydb`.`product_to_options`
-- -----------------------------------------------------
# 商品とオプションの紐付け
CREATE TABLE IF NOT EXISTS `mydb`.`product_to_options` (
  `id` DECIMAL NOT NULL, # 紐付けID
  `product_id` DECIMAL NOT NULL, # 商品ID
  `option_id` DECIMAL NOT NULL, # オプションID
  `sort_order` INT NOT NULL, # ソート順
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id_idx` (`product_id` ASC) VISIBLE,
  INDEX `option_id0_idx` (`option_id` ASC) VISIBLE,
  CONSTRAINT `product_id0`
    FOREIGN KEY (`product_id`)
    REFERENCES `mydb`.`products` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `option_id0`
    FOREIGN KEY (`option_id`)
    REFERENCES `mydb`.`options` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `mydb`.`images`
-- -----------------------------------------------------
# 商品画像マスター
CREATE TABLE IF NOT EXISTS `mydb`.`images` (
  `id` DECIMAL NOT NULL, # 画像ID
  `product_id` DECIMAL NOT NULL, # 商品ID
  `filename` VARCHAR(45) NOT NULL, # 画像ファイル名
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id_idx` (`product_id` ASC) VISIBLE,
  CONSTRAINT `product_id`
    FOREIGN KEY (`product_id`)
    REFERENCES `mydb`.`products` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `mydb`.`taxes`
-- -----------------------------------------------------
# 商品税マスター
CREATE TABLE IF NOT EXISTS `mydb`.`taxes` (
  `id` DECIMAL NOT NULL, # 税ID
  `product_id` DECIMAL NOT NULL, # 商品ID
  `tax_rate` VARCHAR(45) NOT NULL, # 税率
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `product_id_idx` (`product_id` ASC) VISIBLE,
  CONSTRAINT `product_id1`
    FOREIGN KEY (`product_id`)
    REFERENCES `mydb`.`products` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
