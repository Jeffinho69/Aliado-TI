<?php


ini_set('display_errors', 1);
error_reporting(E_ALL);

define('CHAVE_MESTRA', ''); 
define('METODO_CRIPT', '');

function protegerSenha($dado) {
    $ivLength = openssl_cipher_iv_length(METODO_CRIPT);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($dado, METODO_CRIPT, CHAVE_MESTRA, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function revelarSenha($dado) {
    list($encrypted_data, $iv) = explode('::', base64_decode($dado), 2);
    return openssl_decrypt($encrypted_data, METODO_CRIPT, CHAVE_MESTRA, 0, $iv);
}

function gravarLog($pdo, $user_id, $acao, $detalhes = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, acao, detalhes, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $acao, $detalhes, $ip]);
}

// --- FUNÇÃO AUXILIAR: CHAMAR API COM INSISTÊNCIA (RETRY) ---
function chamarAPIcomRetry($url, $data) {
    $tentativas = 0;
    $max_tentativas = 3; // Tenta 3 vezes se der erro de sobrecarga
    
    while ($tentativas < $max_tentativas) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro_curl = curl_errno($ch);
        curl_close($ch);

        // Se der erro de conexão, tenta de novo
        if ($erro_curl) {
            $tentativas++;
            sleep(2); 
            continue;
        }

        $json = json_decode($response, true);

        // Se der erro de "Overloaded" (503) ou "Quota" (429), tenta de novo
        if (isset($json['error'])) {
            $msg = $json['error']['message'];
            if (strpos($msg, 'overloaded') !== false || strpos($msg, 'Quota') !== false || $http_code == 429 || $http_code == 503) {
                $tentativas++;
                sleep(2); // Espera 2 segundos e tenta de novo
                continue;
            }
            // Outros erros (chave errada, modelo inexistente) retorna logo
            return $json;
        }

        // Sucesso
        return $json;
    }

    return ["error" => ["message" => "O sistema do Google está muito ocupado. Tente novamente em 1 minuto."]];
}

// --- FUNÇÃO GEMINI: DIGITALIZAÇÃO (OCR) ---
function processarGemini($arquivos, $modo = 'padrao') {
    $api_key = ''; 
    
    // USANDO "gemini-flash-latest" 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $api_key;

    $parts = [];

    $prompt_texto = "Você é um motor de OCR profissional. Converta imagens/PDFs em HTML para Word.
    REGRAS: 1. Ordem exata. 2. Sem introduções.
    FORMATAÇÃO:
    - Título: <p align='center'><b>TEXTO</b></p>
    - Ementa: <p style='margin-left: 7cm; text-align: justify; font-style: italic;'><b>Ementa:</b> ...</p>
    - Texto: <p style='text-align: justify; text-indent: 1cm;'>...
    - Negrito: <b>...</b>";

    if (is_array($arquivos)) {
        foreach ($arquivos as $arq) {
            if (file_exists($arq['caminho'])) {
                $parts[] = [
                    "inline_data" => [
                        "mime_type" => $arq['mime'],
                        "data" => base64_encode(file_get_contents($arq['caminho']))
                    ]
                ];
            }
        }
    }

    if (empty($parts)) { return "Erro: Nenhum arquivo válido."; }
    
    array_unshift($parts, ["text" => $prompt_texto]);
    $data = ["contents" => [["parts" => $parts]]];

    // CHAMA COM RETRY
    $resultado = chamarAPIcomRetry($url, $data);

    if (isset($resultado['error'])) { return "Erro Google: " . $resultado['error']['message']; }
    
    if (isset($resultado['candidates'][0]['content']['parts'][0]['text'])) {
        $texto = $resultado['candidates'][0]['content']['parts'][0]['text'];
        return str_replace(['```html', '```'], '', trim($texto)); 
    } else {
        return "Sem resposta da IA.";
    }
}

// --- FUNÇÃO CHAT (ALIADA TI) ---
function chatGemini($mensagem, $historico = [], $nome_usuario = 'Colega', $caminho_imagem = null) {
    $api_key = ''; 
    // USANDO "gemini-flash-latest"
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $api_key;

    $contents = [];
    
    $instrucao_sistema = "
    --- DIRETRIZES DE PERSONALIDADE ---
    VOCÊ É: 'Aliada TI'.
    SUA FUNÇÃO: Assistente Virtual oficial da equipe de TI da Câmara Municipal.
    SEU TOM: Profissional, educado, prestativo e acolhedor (família Aliado TI).
    
    O USUÁRIO É: $nome_usuario.
    (Sempre chame o usuário pelo nome $nome_usuario quando apropriado).

    --- SUAS REGRAS DE ATENDIMENTO ---
    1. OBJETIVO: Tente resolver a dúvida técnica do usuário aqui e agora.
    2. LIMITES: Você responde sobre software, formatação, dúvidas de uso e procedimentos.
    3. QUANDO ABRIR CHAMADO: Apenas se o problema for físico (impressora quebrada, sem internet, pc não liga) ou algo que você não consiga resolver conversando.
    4. TRIAGEM: Antes de abrir o chamado, pergunte: O que houve? Desde quando? Qual o impacto?
    
    --- COMANDO PARA ABRIR CHAMADO ---
    Se, e SOMENTE SE, você tiver todas as informações para abrir um chamado técnico, gere APENAS este JSON (sem texto adicional):
    json
    {
        \"acao\": \"abrir_chamado\",
        \"titulo\": \"RESUMO DO PROBLEMA\",
        \"descricao\": \"TRIAGEM REALIZADA PELA IA:\\nProblema relatado: ...\\nTempo: ...\\nImpacto: ...\",
        \"prioridade\": \"baixa|media|alta|critica\"
    }
    
    
    IMPORTANTE: Nunca diga 'não tenho permissão' para dúvidas de TI. Você é a especialista. Ajude o máximo possível.
    ";

    $contents[] = ["role" => "user", "parts" => [["text" => $instrucao_sistema]]];
    $contents[] = ["role" => "model", "parts" => [["text" => "Entendido."]]];

    foreach ($historico as $msg) {
        $texto_limpo = strip_tags($msg['texto']); 
        $role = ($msg['tipo'] == 'user') ? 'user' : 'model';
        $contents[] = ["role" => $role, "parts" => [["text" => $texto_limpo]]];
    }

    $partes_mensagem = [["text" => $mensagem]];

    if ($caminho_imagem && file_exists($caminho_imagem)) {
        $dados_imagem = base64_encode(file_get_contents($caminho_imagem));
        $mime_type = mime_content_type($caminho_imagem);
        $partes_mensagem[] = ["inline_data" => ["mime_type" => $mime_type, "data" => $dados_imagem]];
    }

    $contents[] = ["role" => "user", "parts" => $partes_mensagem];
    $data = ["contents" => $contents];

    // CHAMA COM RETRY
    $resultado = chamarAPIcomRetry($url, $data);

    if (isset($resultado['candidates'][0]['content']['parts'][0]['text'])) {
        return $resultado['candidates'][0]['content']['parts'][0]['text'];
    } else {
        if(isset($resultado['error'])) return "Instabilidade temporária (" . $resultado['error']['message'] . ").";
        return "Não entendi.";
    }
}
?>