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
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Detectar declaración de clase
            if (preg_match('/class\s+(\w+)\s*{?/', $line, $matches)) {
                $ast[] = [
                    'type' => 'class_declaration',
                    'name' => $matches[1]
                ];
                continue;
            }
            
            // Detectar método main
            if (preg_match('/public\s+static\s+void\s+main/', $line)) {
                $ast[] = [
                    'type' => 'main_method'
                ];
                continue;
            }
            
            // Detectar declaración de variables
            if (preg_match('/(int|String|double|boolean)\s+(\w+)\s*=\s*(.+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'variable_declaration',
                    'var_type' => $matches[1],
                    'name' => $matches[2],
                    'value' => $matches[3]
                ];
                continue;
            }
            
            // Detectar System.out.println
            if (preg_match('/System\.out\.println\((.*)\);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'print',
                    'content' => $matches[1]
                ];
                continue;
            }
            
            // Detectar if statement
            if (preg_match('/if\s*\((.*)\)\s*{?/', $line, $matches)) {
                $ast[] = [
                    'type' => 'if_statement',
                    'condition' => $matches[1]
                ];
                continue;
            }
            
            // Detectar asignación de variables
            if (preg_match('/(\w+)\s*=\s*(.+);/', $line, $matches)) {
                $ast[] = [
                    'type' => 'assignment',
                    'name' => $matches[1],
                    'value' => $matches[2]
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
                    $this->variables[$node['name']] = [
                        'type' => $node['var_type'],
                        'value' => $this->evaluateExpression($node['value'])
                    ];
                    $output .= "Declarando variable {$node['name']} de tipo {$node['var_type']} con valor {$this->variables[$node['name']]['value']}\n";
                    break;
                    
                case 'print':
                    $content = $this->evaluateExpression($node['content']);
                    $output .= "Ejecutando System.out.println()\n";
                    $this->consoleOutput .= $content . "\n";
                    break;
                    
                case 'if_statement':
                    $output .= "Evaluando condición: " . $node['condition'] . "\n";
                    break;
                    
                case 'assignment':
                    if (!isset($this->variables[$node['name']])) {
                        throw new Exception("Variable {$node['name']} no declarada");
                    }
                    $this->variables[$node['name']]['value'] = $this->evaluateExpression($node['value']);
                    $output .= "Asignando valor {$node['value']} a {$node['name']}\n";
                    break;
            }
        }
        
        return $output;
    }
    
    private function evaluateExpression($expression) {
        // Si es una concatenación de string con variable
        if (strpos($expression, '+') !== false && strpos($expression, '"') !== false) {
            $parts = explode('+', $expression);
            $result = '';
            foreach ($parts as $part) {
                $part = trim($part);
                if (preg_match('/^"(.*)"$/', $part, $matches)) {
                    $result .= $matches[1];
                } elseif (isset($this->variables[$part])) {
                    $result .= $this->variables[$part]['value'];
                }
            }
            return $result;
        }
        
        // Remover comillas de strings
        if (preg_match('/^"(.*)"$/', $expression, $matches)) {
            return $matches[1];
        }
        
        // Evaluar variables
        if (isset($this->variables[$expression])) {
            return $this->variables[$expression]['value'];
        }
        
        return $expression;
    }
}
