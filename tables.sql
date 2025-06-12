-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema database
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `database` ;

-- -----------------------------------------------------
-- Schema database
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `database` ;

-- -----------------------------------------------------
-- Table `database`.`Client_category`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Client_category` ;

CREATE TABLE IF NOT EXISTS `database`.`Client_category` (
  `idcategory` INT NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(45) NOT NULL,
  `offer_percentage` INT NULL DEFAULT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idcategory`),
  UNIQUE INDEX `category_UNIQUE` (`category` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Contact`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Contact` ;

CREATE TABLE IF NOT EXISTS `database`.`Contact` (
  `idcontact` INT NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(45) NOT NULL,
  `last_name` VARCHAR(45) NOT NULL,
  `email` VARCHAR(45) NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `phone2` VARCHAR(15) NULL DEFAULT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idcontact`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Business`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Business` ;

CREATE TABLE IF NOT EXISTS `database`.`Business` (
  `idbusiness` INT NOT NULL AUTO_INCREMENT,
  `tax_id_number` VARCHAR(12) NOT NULL,
  `business_name` VARCHAR(45) NOT NULL,
  `business_address` VARCHAR(45) NOT NULL,
  `idcontact` INT NOT NULL,
  `categoryid` INT NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idbusiness`),
  UNIQUE INDEX `contact_UNIQUE` (`idcontact` ASC) VISIBLE,
  INDEX `contact_idx` (`idcontact` ASC) VISIBLE,
  INDEX `categoryid_idx` (`categoryid` ASC) VISIBLE,
  CONSTRAINT `categoryid`
    FOREIGN KEY (`categoryid`)
    REFERENCES `database`.`Client_category` (`idcategory`)
    ON DELETE CASCADE,
  CONSTRAINT `contact`
    FOREIGN KEY (`idcontact`)
    REFERENCES `database`.`Contact` (`idcontact`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Club`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Club` ;

CREATE TABLE IF NOT EXISTS `database`.`Club` (
  `idclub` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idclub`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Country`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Country` ;

CREATE TABLE IF NOT EXISTS `database`.`Country` (
  `idcountry` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idcountry`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Sports_jersey`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Sports_jersey` ;

CREATE TABLE IF NOT EXISTS `database`.`Sports_jersey` (
  `iditem` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) NOT NULL,
  `color` VARCHAR(45) NOT NULL,
  `idcountry` INT NOT NULL,
  `idclub` INT NOT NULL,
  `sku` VARCHAR(20) NOT NULL,
  `price` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `description` VARCHAR(100) NULL DEFAULT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`iditem`),
  UNIQUE INDEX `sku_UNIQUE` (`sku` ASC) VISIBLE,
  INDEX `idclub_idx` (`idclub` ASC) VISIBLE,
  INDEX `idcountry_idx` (`idcountry` ASC) VISIBLE,
  CONSTRAINT `idclub`
    FOREIGN KEY (`idclub`)
    REFERENCES `database`.`Club` (`idclub`)
    ON DELETE CASCADE,
  CONSTRAINT `idcountry`
    FOREIGN KEY (`idcountry`)
    REFERENCES `database`.`Country` (`idcountry`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Orders`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Orders` ;

CREATE TABLE IF NOT EXISTS `database`.`Orders` (
  `idorders` INT NOT NULL AUTO_INCREMENT,
  `businessid` INT NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idorders`),
  INDEX `businessid_idx` (`businessid` ASC) VISIBLE,
  CONSTRAINT `businessid`
    FOREIGN KEY (`businessid`)
    REFERENCES `database`.`Business` (`idbusiness`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Order_detail`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Order_detail` ;

CREATE TABLE IF NOT EXISTS `database`.`Order_detail` (
  `iddetail` INT NOT NULL AUTO_INCREMENT,
  `iditem` INT NOT NULL,
  `orderid` INT NOT NULL,
  `quantity` INT NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`iddetail`),
  INDEX `itemid_idx` (`iditem` ASC) VISIBLE,
  INDEX `orderid_idx` (`orderid` ASC) VISIBLE,
  CONSTRAINT `itemid`
    FOREIGN KEY (`iditem`)
    REFERENCES `database`.`Sports_jersey` (`iditem`)
    ON DELETE CASCADE,
  CONSTRAINT `orderid`
    FOREIGN KEY (`orderid`)
    REFERENCES `database`.`Orders` (`idorders`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Size`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Size` ;

CREATE TABLE IF NOT EXISTS `database`.`Size` (
  `idsize` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(7) NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idsize`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `database`.`Size_availability`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `database`.`Size_availability` ;

CREATE TABLE IF NOT EXISTS `database`.`Size_availability` (
  `idavailability` INT NOT NULL AUTO_INCREMENT,
  `iditem` INT NOT NULL,
  `idsize` INT NOT NULL,
  `stock` INT NOT NULL,
`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
`modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`idavailability`),
  INDEX `idsize_idx` (`idsize` ASC) VISIBLE,
  INDEX `iditem_idx` (`iditem` ASC) VISIBLE,
  CONSTRAINT `iditem`
    FOREIGN KEY (`iditem`)
    REFERENCES `database`.`Sports_jersey` (`iditem`)
    ON DELETE CASCADE,
  CONSTRAINT `idsize`
    FOREIGN KEY (`idsize`)
    REFERENCES `database`.`Size` (`idsize`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

GRANT ALL PRIVILEGES ON *.* TO 'user'@'%' IDENTIFIED BY 'password';
FLUSH PRIVILEGES;

--datos para poblar

INSERT INTO `Client_category` (`idcategory`, `category`, `offer_percentage`) VALUES
(1, 'REGULAR', 10),
(2, 'PREFERENCIAL', 20),
(3, 'VIP', 30);

INSERT INTO `Contact` (`idcontact`, `first_name`, `last_name`, `email`, `phone`) VALUES
(1, 'María', 'Gómez', 'maria@example.com', '111111111'),
(2, 'Luis', 'Fernández', 'luis@example.com', '222222222'),
(3, 'Elena', 'Martínez', 'elena@example.com', '333333333');

INSERT INTO `Business` (`idbusiness`, `tax_id_number`, `business_name`, `business_address`, `idcontact`, `categoryid`) VALUES
(1, '30-12345678-1', 'Tienda Deportiva Uno', 'Av. Siempreviva 123', 1, 1),
(2, '30-87654321-2', 'Fanáticos del Fútbol', 'Calle Balón 456', 2, 2);

INSERT INTO `Club` (`idclub`, `name`) VALUES
(1, 'River Plate'),
(2, 'Barcelona FC'),
(3, 'Manchester United');

INSERT INTO `Country` (`idcountry`, `name`) VALUES
(1, 'Argentina'),
(2, 'España'),
(3, 'Inglaterra');

INSERT INTO `Sports_jersey` (`iditem`, `title`, `color`, `idcountry`, `idclub`, `sku`, `price`, `type`, `description`) VALUES
(1, 'Camiseta River 2025', 'Roja y blanca', 1, 1, 'RIV25', 12000, 'Local', 'Camiseta oficial temporada 2025'),
(2, 'Camiseta Barça 2025', 'Azulgrana', 2, 2, 'BAR25', 13500, 'Local', 'Versión oficial del club'),
(3, 'Camiseta United 2025', 'Roja', 3, 3, 'MUN25', 12500, 'Visitante', 'Edición visitante');

INSERT INTO `Size` (`idsize`, `name`) VALUES
(1, 'S'),
(2, 'M'),
(3, 'L'),
(4, 'XL');

INSERT INTO `Size_availability` (`idavailability`, `iditem`, `idsize`, `stock`) VALUES
(1, 1, 1, 5),
(2, 1, 2, 10),
(3, 2, 3, 7),
(4, 3, 4, 12);

INSERT INTO `Orders` (`idorders`, `businessid`) VALUES
(1, 1),
(2, 2);

INSERT INTO `Order_detail` (`iddetail`, `iditem`, `orderid`, `quantity`) VALUES
(1, 1, 1, 2),
(2, 2, 1, 1),
(3, 3, 2, 3);