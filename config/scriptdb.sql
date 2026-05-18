CREATE DATABASE clinica_petshop;
USE clinica_petshop1;

-- TUTOR
CREATE TABLE tutor (
    idTutor INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    cpf VARCHAR(14),
    telefone VARCHAR(20),
    endereco VARCHAR(150)
);

-- PET
CREATE TABLE pet (
    idPet INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    especie VARCHAR(50),
    raca VARCHAR(50),
    peso DECIMAL(5,2),
    sexo VARCHAR(10),
    idTutor INT,
    FOREIGN KEY (idTutor) REFERENCES tutor(idTutor)
);

-- FUNCIONARIO

CREATE TABLE funcionario (
    idFuncionario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    cargo VARCHAR(50),
    telefone VARCHAR(20),
    email varchar(100)
);

-- CLINICA
CREATE TABLE clinica (
    cnpj VARCHAR(20) PRIMARY KEY,
    nome VARCHAR(100),
    endereco VARCHAR(150),
    telefone VARCHAR(20)
);

-- SERVICO
CREATE TABLE servico (
    idServico INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(100),
    valor DECIMAL(10,2)
);

-- PRODUTO
CREATE TABLE produto (
    idProduto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    tipo VARCHAR(50),
    valor DECIMAL(10,2)
);

-- AGENDAMENTO
CREATE TABLE agendamento (
    idAgendamento INT AUTO_INCREMENT PRIMARY KEY,
    data DATE,
    hora TIME,
    idPet INT,
    FOREIGN KEY (idPet) REFERENCES pet(idPet)
);

-- ATENDIMENTO
CREATE TABLE atendimento (
    idAtendimento INT AUTO_INCREMENT PRIMARY KEY,
    data DATE,
    hora TIME,
    idPet INT,
    idFuncionario INT,
    idServico INT,
    FOREIGN KEY (idPet) REFERENCES pet(idPet),
    FOREIGN KEY (idFuncionario) REFERENCES funcionario(idFuncionario),
    FOREIGN KEY (idServico) REFERENCES servico(idServico)
);

-- VENDA
CREATE TABLE venda (
    idVenda INT AUTO_INCREMENT PRIMARY KEY,
    data DATE,
    cnpjClinica VARCHAR(20),
    FOREIGN KEY (cnpjClinica) REFERENCES clinica(cnpj)
);

-- ITEM VENDA
CREATE TABLE item_venda (
    idItem INT AUTO_INCREMENT PRIMARY KEY,
    idVenda INT,
    idProduto INT,
    quantidade INT,
    preco DECIMAL(10,2),
    FOREIGN KEY (idVenda) REFERENCES venda(idVenda),
    FOREIGN KEY (idProduto) REFERENCES produto(idProduto)
);

-- USUARIO (LOGIN)
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE,
    senha VARCHAR(255)
);

-- Adicionar colunas extras à tabela usuario
ALTER TABLE usuario 
ADD COLUMN nome VARCHAR(100) AFTER email,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN status TINYINT DEFAULT 1 COMMENT '1=Ativo, 0=Inativo';

-- Atualizar o usuário existente
UPDATE usuario SET nome = 'Funcionário Teste' WHERE email = 'funcionario@petproject.com';

-- Inserir um usuário de teste (senha: 123456)
INSERT INTO usuario (email, senha) VALUES ('funcionario@petproject.com', '123456');

-- Inserir alguns dados de exemplo para teste
INSERT INTO tutor (nome, cpf, telefone, endereco) VALUES 
('João Silva', '123.456.789-00', '(11) 99999-9999', 'Rua A, 123'),
('Maria Santos', '987.654.321-00', '(11) 88888-8888', 'Rua B, 456');

INSERT INTO pet (nome, especie, raca, peso, sexo, idTutor) VALUES 
('Rex', 'Cachorro', 'Labrador', 25.5, 'Macho', 1),
('Mimi', 'Gato', 'Siamês', 4.2, 'Fêmea', 2);

INSERT INTO funcionario (nome, cargo, telefone) VALUES 
('Dr. Carlos', 'Veterinário', '(11) 77777-7777'),
('Ana Souza', 'Atendente', '(11) 66666-6666');

INSERT INTO servico (tipo, valor) VALUES 
('Consulta', 150.00),
('Banho', 80.00),
('Tosa', 100.00);

INSERT INTO agendamento (data, hora, idPet) VALUES 
(CURDATE(), '14:00:00', 1),
(CURDATE() + INTERVAL 1 DAY, '10:30:00', 2);

INSERT INTO atendimento (data, hora, idPet, idFuncionario, idServico) VALUES 
(CURDATE(), '09:00:00', 1, 1, 1),
(CURDATE(), '10:30:00', 2, 1, 2);