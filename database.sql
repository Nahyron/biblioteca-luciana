-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema biblioteca_vision
-- -----------------------------------------------------

CREATE SCHEMA IF NOT EXISTS `biblioteca_vision` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `biblioteca_vision`;

-- -----------------------------------------------------
-- Table `turmas`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acessos_log`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `turmas`;

CREATE TABLE `turmas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `nome_UNIQUE` (`nome` ASC)
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `usuarios`
-- -----------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `turma` VARCHAR(255) NOT NULL,
  `face_descriptor` LONGTEXT NULL DEFAULT NULL,
  `face_landmarks` LONGTEXT NULL DEFAULT NULL,
  `foto_frontal` VARCHAR(255) NULL DEFAULT NULL,
  `foto_esquerda` VARCHAR(255) NULL DEFAULT NULL,
  `foto_direita` VARCHAR(255) NULL DEFAULT NULL,
  `criado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `rosto_cadastrado_at` DATETIME NULL DEFAULT NULL,
  `ultima_entrada_at` DATETIME NULL DEFAULT NULL,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  INDEX `idx_usuario_turma` (`turma`),
  CONSTRAINT `fk_usuarios_turmas`
    FOREIGN KEY (`turma`)
    REFERENCES `turmas` (`nome`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
)
ENGINE = InnoDB
AUTO_INCREMENT = 10
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `acessos_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `acessos_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `horario_entrada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `acao` VARCHAR(50) NOT NULL DEFAULT 'entrada',
  `operador` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_usuario_acesso` (`usuario_id`),
  CONSTRAINT `fk_usuario_acesso`
    FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE
)
ENGINE = InnoDB
AUTO_INCREMENT = 31
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
USE `biblioteca_vision`;
INSERT IGNORE INTO `turmas` (`nome`) VALUES ('Sem Turma');
INSERT IGNORE INTO `turmas` (`nome`) VALUES ('N/A');

-- -----------------------------------------------------
-- Table `admins`
-- Armazena os usuários administradores do sistema.
-- Senhas armazenadas com password_hash() do PHP (bcrypt).
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `usuario`         VARCHAR(100) NOT NULL,
  `senha_hash`      VARCHAR(255) NOT NULL,
  `senha_resetada`  TINYINT(1) NOT NULL DEFAULT 0,
  `ativo`           TINYINT(1) NOT NULL DEFAULT 1,
  `criado_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `usuario_UNIQUE` (`usuario` ASC)
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- Usuário administrador padrão:
--   Login: admin
--   Senha: admin123
-- (Hash gerado com password_hash('admin123', PASSWORD_BCRYPT))
INSERT IGNORE INTO `admins` (`usuario`, `senha_hash`) VALUES (
  'admin',
  '$2y$12$s4hbE5fgSRkZATJRY09USOxMkBBcZcQMHVk/XvHIUqt4.CDg9vht2'
);

-- -----------------------------------------------------
-- Table `professores`
-- Armazena os professores do sistema.
-- Senhas armazenadas com password_hash() do PHP (bcrypt).
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `professores` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `usuario`         VARCHAR(100) NOT NULL,
  `senha_hash`      VARCHAR(255) NOT NULL,
  `senha_resetada`  TINYINT(1) NOT NULL DEFAULT 0,
  `ativo`           TINYINT(1) NOT NULL DEFAULT 1,
  `criado_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `usuario_UNIQUE` (`usuario` ASC)
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;
