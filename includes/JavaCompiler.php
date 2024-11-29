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
        $ast = [];
        $lines = explode("\n", $code);
        $inIfBlock = false;
        $currentIfNode = null;
        $inElseBlock = false;
        $blockLevel = 0;  // Para rastrear niveles de bloques anidados
        $inForBlock = false;
        $currentForNode = null;
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Detecta estructuras if
            if (preg_match('/if\s*\((.*)\)\s*{?/', $line, $matches)) {
                if ($currentIfNode !== null) {
                    $ast[] = $currentIfNode;
                }
                $inIfBlock = true;
                $inElseBlock = false;
                $currentIfNode = [
                    'type' => 'if_statement',
                    'condition' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                    'body' => [],
                    'else_body' => [],
                    'has_else' => false
                ];
                $blockLevel++;
                continue;
            }
            
            // Detecta bloques else
            if (preg_match('/^\s*else\s*{?\s*$/', $line)) {
                if ($currentIfNode !== null) {
                    $inIfBlock = false;
                    $inElseBlock = true;
                    $currentIfNode['has_else'] = true;
                }
                continue;
            }
            
            // Detecta fin de bloque
            if (trim($line) === '}') {
                $blockLevel--;
                if ($inIfBlock || $inElseBlock) {
                    if ($blockLevel === 0) {
                        $ast[] = $currentIfNode;
                        $currentIfNode = null;
                        $inIfBlock = false;
                        $inElseBlock = false;
                    }
                }
                continue;
            }
            
            // Detecta System.out.println
            if (preg_match('/System\.out\.println\((.*)\);/', $line, $matches)) {
                $printNode = [
                    'type' => 'print',
                    'content' => $matches[1],
                    'line' => $lineNumber + 1
                ];
                
                if ($inForBlock) {
                    $currentForNode['body'][] = $printNode;
                } else if ($currentIfNode !== null) {
                    if ($inElseBlock) {
                        $currentIfNode['else_body'][] = $printNode;
                    } else if ($inIfBlock) {
                        $currentIfNode['body'][] = $printNode;
                    }
                } else {
                    $ast[] = $printNode;
                }
                continue;
            }
            
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
            if (preg_match('/(String|int|double|float)\s+(\w+)\s*=\s*([^;]+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'variable_declaration',
                    'var_type' => $matches[1],
                    'name' => $matches[2],
                    'value' => trim($matches[3]),
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detecta asignaciones a variables existentes
            if (preg_match('/(\w+)\s*=\s*([^;]+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'assignment',
                    'name' => $matches[1],
                    'value' => trim($matches[2]),
                    'line' => $lineNumber + 1
                ];
                continue;
            }
            
            // Detecta estructuras while
            if (preg_match('/while\s*\((.*)\)\s*{?/', $line, $matches)) {
                $inWhileBlock = true;
                $currentWhileNode = [
                    'type' => 'while_statement',
                    'condition' => trim($matches[1]),
                    'line' => $lineNumber + 1,
                    'body' => []
                ];
                continue;
            }
            
            // Detecta break
            if (trim($line) === 'break;') {
                if ($inWhileBlock) {
                    $currentWhileNode['body'][] = [
                        'type' => 'break',
                        'line' => $lineNumber + 1
                    ];
                }
                continue;
            }
            
            // Detecta estructuras for
            if (preg_match('/for\s*\((.*?);(.*?);(.*?)\)\s*{?/', $line, $matches)) {
                $inForBlock = true;
                $currentForNode = [
                    'type' => 'for_statement',
                    'initialization' => trim($matches[1]),
                    'condition' => trim($matches[2]),
                    'increment' => trim($matches[3]),
                    'line' => $lineNumber + 1,
                    'body' => []
                ];
                continue;
            }
        }
        
        // Añadir el último nodo if si existe
        if ($currentIfNode !== null) {
            $ast[] = $currentIfNode;
        }
        
        // Añadir el último nodo for si existe
        if ($currentForNode !== null) {
            $ast[] = $currentForNode;
        }
        
        return $ast;
    }
    
    // Ejecuta el árbol de sintaxis abstracta
    private function executeAST($ast) {
        try {
            $output = '';
            $this->consoleOutput = '';
            $forLoopCount = 0;  // Contador para bucles for
            
            foreach ($ast as $node) {
                switch ($node['type']) {
                    case 'if_statement':
                        $conditionResult = $this->evaluateCondition($node['condition']);
                        $output .= "Evaluando condición if: {$node['condition']} = " . 
                                 ($conditionResult ? "true" : "false") . "\n";
                        
                        if ($conditionResult) {
                            foreach ($node['body'] as $statement) {
                                if ($statement['type'] === 'print') {
                                    $content = $this->evaluatePrintContent($statement['content']);
                                    $output .= "Ejecutando System.out.println() dentro de if\n";
                                    $this->consoleOutput .= $content . "\n";
                                }
                            }
                        } else if (isset($node['has_else']) && $node['has_else']) {
                            foreach ($node['else_body'] as $statement) {
                                if ($statement['type'] === 'print') {
                                    $content = $this->evaluatePrintContent($statement['content']);
                                    $output .= "Ejecutando System.out.println() dentro de else\n";
                                    $this->consoleOutput .= $content . "\n";
                                }
                            }
                        }
                        break;
                        
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
                        
                    case 'for_statement':
                        $forLoopCount++;
                        if ($forLoopCount > 1000) {
                            throw new Exception("Error: Demasiadas iteraciones del bucle for");
                        }
                        
                        // Validar la estructura de inicialización
                        if (preg_match('/(int|double|float)\s+(\w+)\s*=\s*(.+)/', $node['initialization'], $matches)) {
                            $varType = $matches[1];
                            $varName = $matches[2];
                            $varValue = $this->evaluateExpression($matches[3]);
                            
                            // Validar el valor inicial
                            if (!is_numeric($varValue)) {
                                throw new Exception("Error: El valor inicial del for debe ser numérico");
                            }
                            
                            $this->variables[$varName] = [
                                'type' => $varType,
                                'value' => $varValue,
                                'isLoopVariable' => true
                            ];
                            $output .= "Inicializando variable de for {$varName} = {$varValue}\n";
                        }
                        
                        // Validar la condición antes de empezar
                        if (!$this->isValidForCondition($node['condition'])) {
                            throw new Exception("Error: Condición de for inválida");
                        }
                        
                        $iterationCount = 0;
                        while ($this->evaluateCondition($node['condition'])) {
                            $iterationCount++;
                            if ($iterationCount > 1000) {
                                throw new Exception("Error: Bucle for infinito detectado");
                            }
                            
                            foreach ($node['body'] as $statement) {
                                if ($statement['type'] === 'print') {
                                    $content = $this->evaluatePrintContent($statement['content']);
                                    $output .= "Ejecutando System.out.println() dentro de for\n";
                                    $this->consoleOutput .= $content . "\n";
                                }
                            }
                            
                            // Validar y ejecutar incremento
                            $this->executeForIncrement($node['increment'], $output);
                        }
                        
                        // Limpiar variables de bucle
                        $this->cleanupLoopVariables();
                        break;
                }
            }
            return $output;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Evalúa el contenido dentro de un println
    private function evaluatePrintContent($content) {
        // Si es una cadena literal, retorna el contenido sin comillas
        if (preg_match('/^"(.*)"$/', $content, $matches)) {
            return $matches[1];
        }
        
        // Si es una variable simple, retorna su valor
        if (isset($this->variables[$content])) {
            return $this->variables[$content]['value'];
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
                    // Evaluar expresiones aritméticas
                    try {
                        $result .= $this->evaluateExpression($part);
                    } catch (Exception $e) {
                        throw new Exception("Error al evaluar la expresión: " . $part);
                    }
                }
            }
            
            return $result;
        }
        
        return $this->evaluateExpression($content);
    }
    
    // Evalúa expresiones (operaciones matemáticas, variables, etc.)
    private function evaluateExpression($expression) {
        $expression = trim($expression);
        
        // Procesa cadenas literales primero
        if (preg_match('/^"(.*)"$/', $expression, $matches)) {
            return $matches[1];
        }
        
        // Procesa variables
        if (isset($this->variables[$expression])) {
            return $this->variables[$expression]['value'];
        }
        
        // Procesa operaciones aritméticas
        if (preg_match('/^[\d\s\+\-\*\/\(\)]+$|\b[a-zA-Z_]\w*\b/', $expression)) {
            // Reemplaza variables por sus valores
            $evaluatedExpression = preg_replace_callback(
                '/\b([a-zA-Z_]\w*)\b/',
                function($matches) {
                    if (isset($this->variables[$matches[1]])) {
                        $value = $this->variables[$matches[1]]['value'];
                        return $value;
                    }
                    throw new Exception("Variable {$matches[1]} no encontrada");
                },
                $expression
            );
            
            // Evalúa la expresión matemática
            if (preg_match('/^[\d\s\+\-\*\/\(\)\.\d]+$/', $evaluatedExpression)) {
                $evaluatedExpression = str_replace(' ', '', $evaluatedExpression);
                try {
                    return eval("return " . $evaluatedExpression . ";");
                } catch (ParseError $e) {
                    throw new Exception("Error al evaluar la expresión aritmética: " . $evaluatedExpression);
                }
            }
        }
        
        // Procesa números simples incluyendo negativos y decimales
        if (preg_match('/^-?\d*\.?\d+$/', $expression)) {
            return $expression;
        }
        
        return $expression;
    }
    
    private function evaluateCondition($condition) {
        // Reemplazar variables por sus valores
        foreach ($this->variables as $name => $var) {
            // Asegurarse de que los valores negativos se manejen correctamente
            $value = $var['value'];
            if (is_numeric($value) && $value < 0) {
                $condition = str_replace($name, "($value)", $condition);
            } else {
                $condition = str_replace($name, $value, $condition);
            }
        }
        
        // Limpiar espacios extra y normalizar operadores
        $condition = preg_replace('/\s+/', ' ', trim($condition));
        
        // Manejar casos especiales incluyendo valores negativos
        if (preg_match('/(\w+|\(\-?\d+\))\s*(==|!=|<=|>=|<|>)\s*(\-?\d+)/', $condition, $matches)) {
            $varName = $matches[1];
            $operator = $matches[2];
            $value = $matches[3];
            
            // Si es una variable, obtener su valor
            if (isset($this->variables[trim($varName, '()')])) {
                $leftValue = $this->variables[trim($varName, '()')]['value'];
            } else {
                // Si es un número entre paréntesis, eliminar los paréntesis
                $leftValue = trim($varName, '()');
            }
            
            switch ($operator) {
                case '==': return (int)$leftValue === (int)$value;
                case '!=': return (int)$leftValue !== (int)$value;
                case '<=': return (int)$leftValue <= (int)$value;
                case '>=': return (int)$leftValue >= (int)$value;
                case '<': return (int)$leftValue < (int)$value;
                case '>': return (int)$leftValue > (int)$value;
            }
        }
        
        // Evaluar operadores de comparación
        if (strpos($condition, '==') !== false) {
            list($left, $right) = array_map('trim', explode('==', $condition));
            return (int)$this->evaluateExpression($left) === (int)$this->evaluateExpression($right);
        }
        if (strpos($condition, '<=') !== false) {
            list($left, $right) = array_map('trim', explode('<=', $condition));
            return (int)$this->evaluateExpression($left) <= (int)$this->evaluateExpression($right);
        }
        if (strpos($condition, '>=') !== false) {
            list($left, $right) = array_map('trim', explode('>=', $condition));
            return (int)$this->evaluateExpression($left) >= (int)$this->evaluateExpression($right);
        }
        if (strpos($condition, '<') !== false) {
            list($left, $right) = array_map('trim', explode('<', $condition));
            return (int)$this->evaluateExpression($left) < (int)$this->evaluateExpression($right);
        }
        if (strpos($condition, '>') !== false) {
            list($left, $right) = array_map('trim', explode('>', $condition));
            return (int)$this->evaluateExpression($left) > (int)$this->evaluateExpression($right);
        }
        
        return false;
    }
    
    private function isValidForCondition($condition) {
        // Verificar que la condición contenga un operador de comparación válido
        if (!preg_match('/(<=|>=|<|>|==|!=)/', $condition)) {
            return false;
        }
        
        // Verificar que los operandos sean válidos
        $parts = preg_split('/(<=|>=|<|>|==|!=)/', $condition);
        if (count($parts) !== 2) {
            return false;
        }
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (!preg_match('/^\w+$/', $part) && !is_numeric($part)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function executeForIncrement($increment, &$output) {
        // Incremento simple (i++)
        if (preg_match('/(\w+)\+\+/', $increment, $matches)) {
            $varName = $matches[1];
            if (isset($this->variables[$varName])) {
                $this->variables[$varName]['value']++;
                $output .= "Incrementando {$varName} a " . $this->variables[$varName]['value'] . "\n";
            }
        }
        // Incremento con valor (i += 2)
        elseif (preg_match('/(\w+)\s*\+=\s*(\d+)/', $increment, $matches)) {
            $varName = $matches[1];
            $value = (int)$matches[2];
            if (isset($this->variables[$varName])) {
                $this->variables[$varName]['value'] += $value;
                $output .= "Incrementando {$varName} en {$value} a " . $this->variables[$varName]['value'] . "\n";
            }
        }
        // Asignación directa (i = i + 1)
        elseif (preg_match('/(\w+)\s*=\s*(\w+)\s*\+\s*(\d+)/', $increment, $matches)) {
            $varName = $matches[1];
            $value = (int)$matches[3];
            if (isset($this->variables[$varName])) {
                $this->variables[$varName]['value'] += $value;
                $output .= "Incrementando {$varName} en {$value} a " . $this->variables[$varName]['value'] . "\n";
            }
        }
    }
    
    private function cleanupLoopVariables() {
        foreach ($this->variables as $name => $var) {
            if (isset($var['isLoopVariable']) && $var['isLoopVariable']) {
                unset($this->variables[$name]);
            }
        }
    }
}
