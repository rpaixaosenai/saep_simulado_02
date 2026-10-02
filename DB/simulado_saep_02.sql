-- Script de criação e população do banco de dados
-- Sistema de Controle de Estoque Industrial

DROP SCHEMA IF EXISTS simulado_saep_02;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema simulado_saep_02
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `simulado_saep_02` DEFAULT CHARACTER SET utf8 ;
USE `simulado_saep_02` ;

-- -----------------------------------------------------
-- Table `simulado_saep_02`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `simulado_saep_02`.`usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) VISIBLE)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `simulado_saep_02`.`produtos`
-- (produtos/insumos do almoxarifado: chapas, parafusos, tintas, componentes)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `simulado_saep_02`.`produtos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(45) NOT NULL,
  `nome` VARCHAR(200) NOT NULL,
  `categoria` VARCHAR(100) NOT NULL,
  `unidade_medida` VARCHAR(10) NULL,
  `preco_custo` DECIMAL(10,2) NOT NULL,
  `estoque_atual` INT NOT NULL DEFAULT 0,
  `estoque_minimo` INT NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `codigo_UNIQUE` (`codigo` ASC) VISIBLE)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `simulado_saep_02`.`movimentacoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `simulado_saep_02`.`movimentacoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tipo` INT NOT NULL COMMENT '1-entrada 2-saída',
  `data` DATE NOT NULL,
  `quantidade` INT NOT NULL,
  `saldo_anterior` INT NOT NULL,
  `usuarios_id` INT NOT NULL,
  `produtos_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_movimentacoes_usuarios_idx` (`usuarios_id` ASC) VISIBLE,
  INDEX `fk_movimentacoes_produtos1_idx` (`produtos_id` ASC) VISIBLE,
  CONSTRAINT `fk_movimentacoes_usuarios`
    FOREIGN KEY (`usuarios_id`)
    REFERENCES `simulado_saep_02`.`usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_movimentacoes_produtos1`
    FOREIGN KEY (`produtos_id`)
    REFERENCES `simulado_saep_02`.`produtos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- ----------------------------
-- INSERÇÕES
-- ----------------------------

INSERT INTO usuarios (id, nome, email, senha) VALUES
  (1, 'Carlos Almoxarife', 'carlos@industria.com', '123'),
  (2, 'Fernanda Estoque', 'fernanda@industria.com', '123'),
  (3, 'Ricardo Gerente', 'ricardo@industria.com', '456');

INSERT INTO produtos (id, codigo, nome, categoria, unidade_medida, preco_custo, estoque_atual, estoque_minimo, ativo) VALUES
  (1, 'MP001', 'Chapa de Aço 2mm', 'Matéria-Prima', 'un', 150.00, 40, 10, 1),
  (2, 'MP002', 'Parafuso Sextavado M8', 'Fixação', 'un', 0.50, 500, 100, 1),
  (3, 'MP003', 'Tinta Industrial Cinza', 'Pintura', 'L', 45.00, 8, 5, 1),
  (4, 'MP004', 'Componente Eletrônico Sensor X', 'Eletroeletrônico', 'un', 22.00, 3, 5, 1);

INSERT INTO movimentacoes (tipo, data, quantidade, saldo_anterior, usuarios_id, produtos_id) VALUES
  (1, STR_TO_DATE('01/09/2026','%d/%m/%Y'), 20, 20, 1, 1),
  (2, STR_TO_DATE('05/09/2026','%d/%m/%Y'), 100, 600, 2, 2),
  (2, STR_TO_DATE('10/09/2026','%d/%m/%Y'), 5, 8, 3, 4);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
