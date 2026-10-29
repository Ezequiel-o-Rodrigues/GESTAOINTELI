<?php
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/includes/auth.php'; 
 

// Ativar relatório de erros detalhados
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $id = (int)$_POST['id'];
    
    // Debug: Log inicial
    error_log("Iniciando processo de upload...");
    error_log("Tipo: $tipo, ID: $id");
    
    // Validação do tipo
    $tipos_permitidos = ['espetos', 'porcoes', 'bebidas', 'cervejas', 'opcoes_buffet'];
    if (!in_array($tipo, $tipos_permitidos)) {
        $erro = "Tipo inválido!";
        error_log("Tipo inválido selecionado: $tipo");
    } else {
        // Verificar se o item existe
        $idColumns = [
            'espetos' => 'id_espeto',
            'porcoes' => 'id_porcao',
            'bebidas' => 'id_bebida',
            'cervejas' => 'id_cerveja',
            'opcoes_buffet' => 'id_opcao'
        ];
        
        $idColumn = $idColumns[$tipo];
        
        $sql = "SELECT COUNT(*) as total FROM $tipo WHERE $idColumn = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count = $row['total'];
            $stmt->close();

            if ($count === 0) {
                $erro = "Item não encontrado!";
                error_log("Item não encontrado: Tipo $tipo, ID $id");
            } else {
                    // Subir 2 níveis para sair de /admin-seguro-7xY3q9bK/includes
                $pasta_base = realpath(__DIR__ . '/../../public_html/public/images/menu/') . '/';   
               $pasta_tipo = $pasta_base . $tipo . '/';
                
                // Debug: Verificar caminhos
                error_log("Pasta base: $pasta_base");
                error_log("Pasta tipo: $pasta_tipo");
                
                // Criar pasta se não existir com verificação
                $pasta_tipo = $pasta_base . $tipo . '/';
if (!is_dir($pasta_tipo)) {
    if (!mkdir($pasta_tipo, 0755, true)) {
        $erro = "Falha ao criar diretório para $tipo. Verifique as permissões.";
        error_log($erro);
        error_log("Tentando criar em: " . $pasta_tipo);
        die($erro);
    }
}
                
                // Verificar se a pasta é gravável
                if (!is_writable($pasta_tipo)) {
                    $erro = "O diretório de imagens não tem permissão de escrita. Verifique as permissões.";
                    error_log("Diretório sem permissão de escrita: $pasta_tipo");
                    error_log("Permissões atuais: " . substr(sprintf('%o', fileperms($pasta_tipo)), -4));
                } else {
                    error_log("Diretório com permissão de escrita confirmada");
                }
                
                // Validar e processar imagem
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($extensao, $extensoes_permitidas)) {
                    $erro = "Tipo de arquivo não permitido! Apenas JPG, JPEG, PNG e GIF são permitidos.";
                    error_log("Tipo de arquivo não permitido: $extensao");
                } else {
                    // Gerar nome para a imagem
                    $nome_arquivo = $tipo . '_' . $id . '.' . $extensao;
                    $caminho_completo = $pasta_tipo . $nome_arquivo;
                    
                    // Debug: Informações do arquivo
                    error_log("Nome do arquivo: $nome_arquivo");
                    error_log("Caminho completo: $caminho_completo");
                    error_log("Arquivo temporário: " . $_FILES['imagem']['tmp_name']);
                    error_log("Tamanho do arquivo: " . $_FILES['imagem']['size'] . " bytes");
                    
                    // Verificar e remover imagem antiga se existir
                    $sql_old_image = "SELECT imagem FROM $tipo WHERE $idColumn = ?";
                    $stmt_old = $conn->prepare($sql_old_image);
                    $stmt_old->bind_param("i", $id);
                    $stmt_old->execute();
                    $stmt_old->bind_result($old_image);
                    $stmt_old->fetch();
                    
                    if ($old_image && file_exists($pasta_tipo . $old_image)) {
                        error_log("Encontrada imagem antiga: " . $pasta_tipo . $old_image);
                        if (!unlink($pasta_tipo . $old_image)) {
                            error_log("Falha ao remover imagem antiga: " . $pasta_tipo . $old_image);
                        } else {
                            error_log("Imagem antiga removida com sucesso");
                        }
                    }
                    $stmt_old->close();
                    
                    // Tentar mover o arquivo
                    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_completo)) {
                        // Verificar se o arquivo realmente foi criado
                        if (!file_exists($caminho_completo)) {
                            $erro = "O upload parece ter sido bem-sucedido, mas o arquivo não foi encontrado no destino.";
                            error_log("Erro crítico: Arquivo não encontrado após upload: $caminho_completo");
                        } else {
                            error_log("Arquivo movido com sucesso para: $caminho_completo");
                            error_log("Tamanho do arquivo movido: " . filesize($caminho_completo) . " bytes");
                            
                            // Atualizar banco de dados
                            $updateStmt = $conn->prepare("UPDATE $tipo SET imagem = ? WHERE $idColumn = ?");
                            $updateStmt->bind_param("si", $nome_arquivo, $id);
                            
                            if ($updateStmt->execute()) {
                                $msg = "Imagem atualizada com sucesso!";
                                error_log("Banco de dados atualizado com sucesso");
                            } else {
                                $erro = "Erro ao atualizar banco de dados: " . $conn->error;
                                error_log("Erro ao atualizar BD: " . $conn->error);
                                
                                // Remove a imagem que foi enviada mas não foi registrada no banco
                                if (file_exists($caminho_completo)) {
                                    if (!unlink($caminho_completo)) {
                                        error_log("Falha ao remover arquivo não registrado: $caminho_completo");
                                    }
                                }
                            }
                            $updateStmt->close();
                        }
                    } else {
                        $errorCode = $_FILES['imagem']['error'];
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o upload_max_filesize do php.ini',
                            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o MAX_FILE_SIZE especificado no formulário HTML',
                            UPLOAD_ERR_PARTIAL => 'O arquivo foi apenas parcialmente enviado',
                            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                            UPLOAD_ERR_NO_TMP_DIR => 'Faltando pasta temporária',
                            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo no disco',
                            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload',
                        ];
                        $erro = "Erro no upload da imagem: " . ($errorMessages[$errorCode] ?? 'Erro desconhecido');
                        error_log("Erro no upload: " . $erro);
                        error_log("Código do erro: " . $errorCode);
                        error_log("Permissões da pasta: " . substr(sprintf('%o', fileperms($pasta_tipo)), -4));
                    }
                }
            }
        } else {
            $erro = "Erro na preparação da consulta: " . $conn->error;
            error_log("Erro na preparação da consulta: " . $conn->error);
        }
    }
}

// Lista de itens para seleção
$tipos = ['espetos', 'porcoes', 'bebidas', 'cervejas', 'opcoes_buffet'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de Imagens - Cardápio JNR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #1a1a2e;
            --bg-panel: #16213e;
            --text-primary: #e6e6e6;
            --text-secondary: #a9a9a9;
            --accent-color: #4a6fa5;
            --border-color: #2c3e50;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --card-bg: rgba(30, 30, 46, 0.8);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            background-image: linear-gradient(to bottom right, #1a1a2e, #16213e, #0f3460);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            color: var(--text-primary);
            margin: 0;
            font-size: 2.5rem;
            background: linear-gradient(to right, #4a6fa5, #7eb4e2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid var(--accent-color);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
        }
        
        .card-title {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-container {
            display: grid;
            gap: 20px;
        }
        
        .form-group {
            display: grid;
            gap: 8px;
        }
        
        .form-group label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        input, select, textarea {
            padding: 12px 15px;
            background-color: rgba(40, 40, 60, 0.7);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.3);
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, #4a6fa5, #5a86c1);
            color: white;
            box-shadow: 0 4px 10px rgba(74, 111, 165, 0.4);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #3a5a8c, #4a6fa5);
            box-shadow: 0 6px 15px rgba(74, 111, 165, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-primary:active {
            transform: translateY(1px);
        }
        
        .msg {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--text-primary);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid var(--success-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .erro {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--text-primary);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid #f44336;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box {
            background-color: rgba(74, 111, 165, 0.2);
            border-left: 4px solid var(--accent-color);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        
        .info-box h3 {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }
        
        .info-box ul {
            padding-left: 20px;
        }
        
        .info-box li {
            margin-bottom: 8px;
            color: var(--text-secondary);
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            background-color: rgba(40, 40, 60, 0.3);
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            min-height: 180px;
        }
        
        .file-input-label:hover {
            border-color: var(--accent-color);
            background-color: rgba(40, 40, 60, 0.5);
        }
        
        .file-input-label i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }
        
        .file-input-label .file-name {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .preview-container {
            margin-top: 20px;
            text-align: center;
            display: none;
        }
        
        .preview-container img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--accent-color);
        }

        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="page-title">📷 Upload de Imagens - Cardápio JNR</h1>
        </div>
        
        <?php if ($msg): ?>
            <div class="msg">
                <i class="fas fa-check-circle"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="erro">
                <i class="fas fa-exclamation-circle"></i>
                <?= $erro ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Informações Importantes</h3>
            <ul>
                <li>Formatos aceitos: JPG, JPEG, PNG, GIF</li>
                <li>Tamanho máximo recomendado: 2MB</li>
                <li>As imagens serão salvas em: /public/images/menu/</li>
                <li>Para cada item, selecione o tipo e informe o ID</li>
                <li>IDs podem ser encontrados na página de listagens</li>
            </ul>
        </div>
        
        <div class="card">
            <h2 class="card-title"><i class="fas fa-cloud-upload-alt"></i> Enviar Nova Imagem</h2>
            <form method="post" enctype="multipart/form-data" class="form-container">
                <div class="form-group">
                    <label>Tipo de Item:</label>
                    <select name="tipo" required>
                        <option value="">Selecione um tipo</option>
                        <?php foreach($tipos as $t): ?>
                            <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>ID do Item:</label>
                    <input type="number" name="id" required min="1" placeholder="Ex: 1, 2, 3...">
                </div>

                 <a href="relatoriogeral.php" class="back-link">
                <i class="fas fa-arrow-left"></i> encontrar o ID do item
            </a>
                
                <div class="form-group">
                    <label>Selecione a Imagem:</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-label" id="file-label">
                            <i class="fas fa-file-image"></i>
                            <div>Clique para selecionar uma imagem</div>
                            <div class="file-name" id="file-name">Nenhum arquivo selecionado</div>
                        </div>
                        <input type="file" name="imagem" id="file-input" accept="image/*" required>
                    </div>
                    <div class="preview-container" id="preview-container">
                        <img id="image-preview" src="" alt="Pré-visualização">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Enviar Imagem
                </button>
            </form>
            
            <a href="painel.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o Painel
            </a>
        </div>
    </div>

    <script>
        // Atualizar nome do arquivo selecionado
        document.getElementById('file-input').addEventListener('change', function(e) {
            const fileName = this.files[0] ? this.files[0].name : 'Nenhum arquivo selecionado';
            document.getElementById('file-name').textContent = fileName;
            
            // Exibir pré-visualização da imagem
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('image-preview').src = e.target.result;
                    document.getElementById('preview-container').style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                document.getElementById('preview-container').style.display = 'none';
            }
        });
        
        // Feedback visual ao passar o mouse sobre a área de upload
        const fileLabel = document.getElementById('file-label');
        fileLabel.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = '#4a6fa5';
            fileLabel.style.backgroundColor = 'rgba(40, 40, 60, 0.7)';
        });
        
        fileLabel.addEventListener('dragleave', () => {
            fileLabel.style.borderColor = '#2c3e50';
            fileLabel.style.backgroundColor = 'rgba(40, 40, 60, 0.3)';
        });
        
        fileLabel.addEventListener('drop', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = '#2c3e50';
            fileLabel.style.backgroundColor = 'rgba(40, 40, 60, 0.3)';
        });
    </script>
</body>
</html>