<?php
class JavaCompiler {
    // Almacena las variables del programa y sus valores durante la ejecución
    private $variables = [];
    // Almacena la salida del proceso de compilación
    private $output = '';
    // Almacena la salida que se mostrará en la consola simulada
    private $consoleOutput = '';
    
    // Método principal que inicia el proceso de compilación
    public function compile($code) {
        try {
            // Genera el árbol de sintaxis abstracta (AST) a partir del código
            $ast = $this->parseCode($code);
            // Ejecuta el AST y retorna los resultados
            return [
                'compilacion' => $this->executeAST($ast),  // Log del proceso de compilación
                'consola' => $this->consoleOutput         // Salida que verá el usuario
            ];
        } catch (Exception $e) {
            // Si hay algún error, lo reporta tanto en compilación como en consola
            return [
                'compilacion' => "Error: " . $e->getMessage(),
                'consola' => "Error en tiempo de ejecución: " . $e->getMessage()
            ];
        }
    }
    
    // Analiza el código línea por línea y construye el AST
    private function parseCode($code) {
        $ast = [];  // Aquí se almacenarán los nodos del árbol
        $lines = explode("\n", $code);  // Divide el código en líneas
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);  // Elimina espacios en blanco al inicio y final
            if (empty($line)) continue;  // Salta líneas vacías
            
            // Detecta declaración de clase (ej: public class MiClase)
            if (preg_match('/class\s+(\w+)\s*{?/', $line, $matches)) {
                $ast[] = [
                    'type' => 'class_declaration',
                    'name' => $matches[1],
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detecta método main
            if (preg_match('/public\s+static\s+void\s+main/', $line)) {
                $ast[] = [
                    'type' => 'main_method',
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detecta declaraciones de variables con inicialización
            // Ej: int x = 5; o String mensaje = "Hola";
            if (preg_match('/(String|int|double|float)\s+(\w+)\s*=\s*(.+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'variable_declaration',
                    'var_type' => $matches[1],  // tipo de variable
                    'name' => $matches[2],      // nombre de variable
                    'value' => $matches[3],     // valor inicial
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detecta asignaciones a variables existentes
            // Ej: x = 10;
            if (preg_match('/(\w+)\s*=\s*(.+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'assignment',
                    'name' => $matches[1],
                    'value' => $matches[2],
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detecta llamadas a System.out.println
            if (preg_match('/System\.out\.println\((.*)\);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'print',
                    'content' => $matches[1],
                    'line' => $lineNumber + 1
                ];
                continue;
            }
        }
        
        return $ast;
    }
    
    // Ejecuta el árbol de sintaxis abstracta
    private function executeAST($ast) {
        $output = '';
        $this->consoleOutput = '';
        
        foreach ($ast as $node) {
            switch ($node['type']) {
                case 'class_declaration':
                    // Registra la declaración de la clase
                    $output .= "Declarando clase: " . $node['name'] . "\n";
                    break;
                    
                case 'main_method':
                    // Registra el inicio del método main
                    $output .= "Iniciando método main\n";
                    break;
                    
                case 'variable_declaration':
                    // Evalúa y almacena una nueva variable
                    $valor = $this->evaluateExpression($node['value']);
                    $this->variables[$node['name']] = [
                        'type' => $node['var_type'],
                        'value' => $valor
                    ];
                    $output .= "Declarando variable {$node['name']} de tipo {$node['var_type']} con valor {$valor}\n";
                    break;
                    
                case 'assignment':
                    // Verifica que la variable exista antes de asignar
                    if (!isset($this->variables[$node['name']])) {
                        throw new Exception("Variable {$node['name']} no declarada en línea {$node['line']}");
                    }
                    $valor = $this->evaluateExpression($node['value']);
                    $this->variables[$node['name']]['value'] = $valor;
                    $output .= "Asignando valor {$valor} a {$node['name']}\n";
                    break;
                    
                case 'print':
                    // Evalúa y muestra el contenido en la consola
                    $content = $this->evaluatePrintContent($node['content']);
                    $output .= "Ejecutando System.out.println()\n";
                    $this->consoleOutput .= $content . "\n";
                    break;
            }
        }
        
        return $output;
    }
    
    // Evalúa el contenido dentro de un println
    private function evaluatePrintContent($content) {
        // Si es una variable simple, retorna su valor
        if (isset($this->variables[$content])) {
            return $this->variables[$content]['value'];
        }

        // Si es una cadena literal, retorna el contenido sin comillas
        if (preg_match('/^"(.*)"$/', $content, $matches)) {
            return $matches[1];
        }

        // Si es una concatenación (contiene el operador +)
        if (strpos($content, '+') !== false) {
            $parts = explode('+', $content);
            $result = '';
            
            foreach ($parts as $part) {
                $part = trim($part);
                
                // Procesa cada parte de la concatenación
                if (preg_match('/^"(.*)"$/', $part, $matches)) {
                    $result .= $matches[1];  // Cadena literal
                }
                elseif (isset($this->variables[$part])) {
                    $result .= $this->variables[$part]['value'];  // Variable
                }
                else {
                    $result .= $this->evaluateExpression($part);  // Expresión
                }
            }
            
            return $result;
        }

        // Si no es ninguno de los casos anteriores, evalúa como expresión
        return $this->evaluateExpression($content);
    }
    
    // Evalúa expresiones (operaciones matemáticas, variables, etc.)
    private function evaluateExpression($expression) {
        $expression = trim($expression);
        
        // Procesa cadenas literales
        if (preg_match('/^"(.*)"$/', $expression, $matches)) {
            return $matches[1];
        }
        
        // Procesa variables
        if (isset($this->variables[$expression])) {
            return $this->variables[$expression]['value'];
        }
        
        // Procesa operaciones aritméticas
        if (preg_match('/[\d\s\+\-\*\/\(\)]|\b[a-zA-Z_]\w*\b/', $expression)) {
            // Reemplaza variables por sus valores
            $evaluatedExpression = preg_replace_callback(
                '/\b([a-zA-Z_]\w*)\b/',
                function($matches) {
                    if (isset($this->variables[$matches[1]])) {
                        return $this->variables[$matches[1]]['value'];
                    }
                    throw new Exception("Variable {$matches[1]} no encontrada");
                },
                $expression
            );
            
            // Evalúa la expresión matemática
            if (preg_match('/^[\d\s\+\-\*\/\(\)]+$/', $evaluatedExpression)) {
                $evaluatedExpression = str_replace(' ', '', $evaluatedExpression);
                try {
                    return eval("return " . $evaluatedExpression . ";");
                } catch (ParseError $e) {
                    throw new Exception("Error al evaluar la expresión aritmética: " . $evaluatedExpression);
                }
            }
        }
        
        // Procesa números simples
        if (is_numeric($expression)) {
            return $expression;
        }
        
        return $expression;
    }
}
