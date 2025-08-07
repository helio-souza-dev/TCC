-- Create database
CREATE DATABASE IF NOT EXISTS sistema_chamadas;
USE sistema_chamadas;

-- Users table
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'professor') NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    matricula VARCHAR(20) UNIQUE NOT NULL,
    turma VARCHAR(50),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE chamadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT,
    turma VARCHAR(50) NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    data_chamada DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id)
);

-- Attendance table
CREATE TABLE presencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chamada_id INT,
    aluno_id INT,
    presente BOOLEAN DEFAULT FALSE,
    observacao TEXT,
    FOREIGN KEY (chamada_id) REFERENCES chamadas(id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@escola.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample students
INSERT INTO alunos (nome, matricula, turma) VALUES 
('João Silva', '2024001', '3A'),
('Maria Santos', '2024002', '3A'),
('Pedro Oliveira', '2024003', '3A'),
('Ana Costa', '2024004', '3B'),
('Carlos Lima', '2024005', '3B');
