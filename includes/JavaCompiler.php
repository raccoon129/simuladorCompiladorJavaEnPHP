<?php
class JavaCompiler {
    private $variables = [];
    private $output = '';
    private $consoleOutput = '';
    
    public function compile($code) {
        try {
            $ast = $this->parseCode($code);
            return [
                'compilacion' => $this->executeAST($ast),
                'consola' => $this->consoleOutput
            ];
        } catch (Exception $e) {
            return [
                'compilacion' => "Error: " . $e->getMessage(),
                'consola' => "Error en tiempo de ejecución: " . $e->getMessage()
            ];
        }
    }
    
    private function parseCode($code) {
        $ast = [];
        $lines = explode("\n", $code);
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Detectar declaración de clase
            if (preg_match('/class\s+(\w+)\s*{?/', $line, $matches)) {
                $ast[] = [
                    'type' => 'class_declaration',
                    'name' => $matches[1],
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detectar método main
            if (preg_match('/public\s+static\s+void\s+main/', $line)) {
                $ast[] = [
                    'type' => 'main_method',
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detectar declaración de variables (incluyendo String)
            if (preg_match('/(String|int|double|float)\s+(\w+)\s*=\s*(.+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'variable_declaration',
                    'var_type' => $matches[1],
                    'name' => $matches[2],
                    'value' => $matches[3],
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detectar asignaciones con operaciones
            if (preg_match('/(\w+)\s*=\s*(.+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'assignment',
                    'name' => $matches[1],
                    'value' => $matches[2],
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detectar System.out.println
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
    
    private function executeAST($ast) {
        $output = '';
        $this->consoleOutput = '';
        
        foreach ($ast as $node) {
            switch ($node['type']) {
                case 'class_declaration':
                    $output .= "Declarando clase: " . $node['name'] . "\n";
                    break;
                    
                case 'main_method':
                    $output .= "Iniciando método main\n";
                    break;
                    
                case 'variable_declaration':
                    $valor = $this->evaluateExpression($node['value']);
                    $this->variables[$node['name']] = [
                        'type' => $node['var_type'],
                        'value' => $valor
                    ];
                    $output .= "Declarando variable {$node['name']} de tipo {$node['var_type']} con valor {$valor}\n";
                    break;
                    
                case 'assignment':
                    if (!isset($this->variables[$node['name']])) {
                        throw new Exception("Variable {$node['name']} no declarada en línea {$node['line']}");
                    }
                    $valor = $this->evaluateExpression($node['value']);
                    $this->variables[$node['name']]['value'] = $valor;
                    $output .= "Asignando valor {$valor} a {$node['name']}\n";
                    break;
                    
                case 'print':
                    $content = $this->evaluatePrintContent($node['content']);
                    $output .= "Ejecutando System.out.println()\n";
                    $this->consoleOutput .= $content . "\n";
                    break;
            }
        }
        
        return $output;
    }
    
    private function evaluatePrintContent($content) {
        // Si el contenido es solo una variable
        if (isset($this->variables[$content])) {
            return $this->variables[$content]['value'];
        }

        // Si el contenido es una cadena literal
        if (preg_match('/^"(.*)"$/', $content, $matches)) {
            return $matches[1];
        }

        // Si es una concatenación (contiene + y posiblemente strings)
        if (strpos($content, '+') !== false) {
            $parts = explode('+', $content);
            $result = '';
            
            foreach ($parts as $part) {
                $part = trim($part);
                
                // Si es una cadena literal
                if (preg_match('/^"(.*)"$/', $part, $matches)) {
                    $result .= $matches[1];
                }
                // Si es una variable
                elseif (isset($this->variables[$part])) {
                    $result .= $this->variables[$part]['value'];
                }
                // Si es una expresión aritmética
                else {
                    $result .= $this->evaluateExpression($part);
                }
            }
            
            return $result;
        }

        // Si no es ninguno de los casos anteriores, intentar evaluar como expresión
        return $this->evaluateExpression($content);
    }
    
    private function evaluateExpression($expression) {
        // Remover espacios en blanco
        $expression = trim($expression);
        
        // Si es una cadena literal, retornarla sin las comillas
        if (preg_match('/^"(.*)"$/', $expression, $matches)) {
            return $matches[1];
        }
        
        // Si es una variable simple, retornar su valor
        if (isset($this->variables[$expression])) {
            return $this->variables[$expression]['value'];
        }
        
        // Si es una operación aritmética
        if (preg_match('/[\d\s\+\-\*\/\(\)]|\b[a-zA-Z_]\w*\b/', $expression)) {
            // Reemplazar variables por sus valores
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
            
            // Si solo contiene números y operadores
            if (preg_match('/^[\d\s\+\-\*\/\(\)]+$/', $evaluatedExpression)) {
                $evaluatedExpression = str_replace(' ', '', $evaluatedExpression);
                try {
                    return eval("return " . $evaluatedExpression . ";");
                } catch (ParseError $e) {
                    throw new Exception("Error al evaluar la expresión aritmética: " . $evaluatedExpression);
                }
            }
        }
        
        // Si es un número simple
        if (is_numeric($expression)) {
            return $expression;
        }
        
        return $expression;
    }
}
