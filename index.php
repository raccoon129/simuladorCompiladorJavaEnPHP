<?php
// Archivo: index.php
require_once 'includes/AnalizadorLexico.php';
require_once 'includes/AnalizadorSintactico.php';
require_once 'includes/AnalizadorSemantico.php';
require_once 'includes/JavaCompiler.php';

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/errors.log');

// Procesar la compilación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    
    // Análisis Léxico
    $analizadorLexico = new AnalizadorLexico($code);
    $tokens = $analizadorLexico->analizar();
    $resultadoLexico = $analizadorLexico->obtenerResultadoHTML();
    
    // Análisis Sintáctico
    $analizadorSintactico = new AnalizadorSintactico($tokens);
    $arbolSintactico = $analizadorSintactico->analizar();
    $resultadoSintactico = $analizadorSintactico->obtenerResultadoHTML();
    
    // Análisis Semántico
    $analizadorSemantico = new AnalizadorSemantico();
    $resultadoSemantico = $analizadorSemantico->analizar($arbolSintactico);
    $resultadoSemanticoHTML = $analizadorSemantico->obtenerResultadoHTML();
    
    // Compilación final
    $compiler = new JavaCompiler();
    $resultado = $compiler->compile($code);
    
    header('Content-Type: application/json');
    echo json_encode([
        'lexicoHTML' => $resultadoLexico,
        'sintacticoHTML' => $resultadoSintactico,
        'semanticoHTML' => $resultadoSemanticoHTML,
        'result' => $resultado
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compilador Java</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="assets/js/compiler.js"></script>
    <style>
        .code-editor {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-code-square"></i> Compilador Java en PHP</a>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-earmark-code"></i> Código Java
                            </h5>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetExample()">
                                <i class="bi bi-arrow-counterclockwise"></i> Cargar Ejemplo
                            </button>
                        </div>
                        <textarea id="code" class="form-control code-editor" rows="15" 
                            placeholder="Escribe tu código Java aquí..."></textarea>
                        <div class="text-center mt-3">
                            <button onclick="compile()" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-play-fill"></i> Compilar y Ejecutar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-terminal"></i> Resultado de Ejecución
                        </h5>
                        <pre id="output" class="form-control code-editor bg-light" 
                            style="height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>

                <!-- Simulación de Consola -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-terminal-fill"></i> Simulación de Consola
                        </h5>
                    </div>
                    <div class="card-body bg-dark">
                        <pre id="console-output" class="code-editor text-light" 
                            style="height: 150px; overflow-y: auto; margin: 0; padding: 10px; font-family: 'Consolas', monospace;">Esperando ejecución...</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fases del Compilador -->
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-ol"></i> Análisis Léxico
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="analisis-lexico" class="p-3" style="height: 400px; overflow-y: auto;">
                            <div class="text-muted text-center py-5">
                                Esperando código para analizar...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-diagram-2"></i> Análisis Sintáctico
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="analisis-sintactico" class="p-3" style="height: 400px; overflow-y: auto;">
                            <div class="text-muted text-center py-5">
                                Esperando análisis léxico...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-check-circle"></i> Análisis Semántico
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="analisis-semantico" class="p-3" style="height: 400px; overflow-y: auto;">
                            <div class="text-muted text-center py-5">
                                Esperando análisis sintáctico...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetExample() {
            document.getElementById('code').value = `public class HelloWorld {
    public static void main(String[] args) {
        String mensaje = "¡Hola, Mundo!";
        System.out.println(mensaje);
        
        int numero = 42;
        System.out.println("El número es: " + numero);
    }
}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            resetExample();
        });
    </script>
</body>
</html>