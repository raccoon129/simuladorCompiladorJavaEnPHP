<?php
class AnalizadorSemantico {
    private $tablaSímbolos = [];
    private $errores = [];
    private $alcanceActual = 'global';

    // Definir tipos de datos válidos y sus reglas
    private $tiposDatos = [
        'int' => [
            'regex' => '/^-?\d+$/',
            'mensaje' => 'debe ser un número entero'
        ],
        'double' => [
            'regex' => '/^-?\d*\.?\d+$/',
            'mensaje' => 'debe ser un número decimal'
        ],
        'String' => [
            'regex' => '/^".*"$/',
            'mensaje' => 'debe ser una cadena entre comillas dobles'
        ],
        'boolean' => [
            'regex' => '/^(true|false)$/',
            'mensaje' => 'debe ser true o false'
        ]
    ];

    public function analizar($arbolSintactico) {
        $this->tablaSímbolos = [];
        $this->errores = [];
        
        foreach ($arbolSintactico as $nodo) {
            $this->analizarNodo($nodo);
        }
        
        return [
            'tablaSímbolos' => $this->tablaSímbolos,
            'errores' => $this->errores
        ];
    }

    private function analizarNodo($nodo) {
        switch ($nodo['tipo']) {
            case 'clase':
                $this->alcanceActual = $nodo['nombre'];
                $this->tablaSímbolos[$this->alcanceActual] = [
                    'tipo' => 'clase',
                    'métodos' => [],
                    'variables' => []
                ];
                break;

            case 'metodo':
                $this->validarMetodo($nodo);
                break;

            case 'variable':
                if ($nodo['tipoVariable'] !== 'else') {
                    $this->validarVariable($nodo);
                }
                break;

            case 'if':
                $this->validarIf($nodo);
                break;

            case 'for':
                $this->validarFor($nodo);
                break;
        }
    }

    private function validarVariable($nodo) {
        $nombreVariable = $nodo['nombre'];
        $tipoVariable = $nodo['tipoVariable'];
        $valorInicial = $nodo['valorInicial'] ?? null;
        $linea = $nodo['linea'];

        // Verificar si la variable ya está declarada
        if (isset($this->tablaSímbolos[$this->alcanceActual]['variables'][$nombreVariable]) &&
            !isset($this->tablaSímbolos[$this->alcanceActual]['variables'][$nombreVariable]['esVariableFor'])) {
            $this->errores[] = "Error en línea $linea: Variable '$nombreVariable' ya declarada";
            return;
        }

        // Verificar si el tipo de dato es válido
        if (!isset($this->tiposDatos[$tipoVariable])) {
            $this->errores[] = "Error en línea $linea: Tipo de dato '$tipoVariable' no válido";
            return;
        }

        // Si hay valor inicial, validar el tipo
        if ($valorInicial !== null) {
            $this->validarTipoDato($tipoVariable, $valorInicial, $linea);
        }

        // Registrar la variable en la tabla de símbolos
        $this->tablaSímbolos[$this->alcanceActual]['variables'][$nombreVariable] = [
            'tipo' => $tipoVariable,
            'valorInicial' => $valorInicial,
            'línea' => $linea
        ];
    }

    private function validarTipoDato($tipo, $valor, $linea) {
        // Remover comillas para strings si existen
        $valorLimpio = trim($valor, '"');
        
        switch ($tipo) {
            case 'int':
                if (!preg_match($this->tiposDatos['int']['regex'], $valorLimpio)) {
                    $this->errores[] = "Error en línea $linea: El valor '$valor' no es válido para tipo int, " . 
                                     $this->tiposDatos['int']['mensaje'];
                } elseif (abs((int)$valorLimpio) > PHP_INT_MAX) {
                    $this->errores[] = "Error en línea $linea: El valor está fuera del rango permitido para int";
                }
                break;

            case 'double':
                if (!preg_match($this->tiposDatos['double']['regex'], $valorLimpio)) {
                    $this->errores[] = "Error en línea $linea: El valor '$valor' no es válido para tipo double, " . 
                                     $this->tiposDatos['double']['mensaje'];
                }
                break;

            case 'String':
                if (!preg_match($this->tiposDatos['String']['regex'], $valor)) {
                    $this->errores[] = "Error en línea $linea: El valor '$valor' no es válido para tipo String, " . 
                                     $this->tiposDatos['String']['mensaje'];
                }
                break;

            case 'boolean':
                if (!preg_match($this->tiposDatos['boolean']['regex'], $valorLimpio)) {
                    $this->errores[] = "Error en línea $linea: El valor '$valor' no es válido para tipo boolean, " . 
                                     $this->tiposDatos['boolean']['mensaje'];
                }
                break;
        }
    }

    private function validarMetodo($nodo) {
        $nombreMetodo = $nodo['nombre'];
        
        // Validar que el método main tenga la firma correcta
        if ($nombreMetodo === 'main') {
            if (!isset($nodo['esStatic']) || !$nodo['esStatic']) {
                $this->errores[] = "Error en línea {$nodo['linea']}: El método main debe ser static";
            }
            if (($nodo['tipoRetorno'] ?? '') !== 'void') {
                $this->errores[] = "Error en línea {$nodo['linea']}: El método main debe retornar void";
            }
        }

        // Registrar el método en la tabla de símbolos
        $this->tablaSímbolos[$this->alcanceActual]['métodos'][$nombreMetodo] = [
            'modificador' => $nodo['modificador'],
            'tipoRetorno' => $nodo['tipoRetorno'] ?? 'void',
            'parametros' => $nodo['parametros'] ?? [],
            'esStatic' => $nodo['esStatic'] ?? false,
            'línea' => $nodo['linea']
        ];
    }

    private function validarIf($nodo) {
        if (!isset($nodo['condicion'])) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Condición if faltante";
            return;
        }
        
        // Validar estructura de la condición
        $condicion = trim($nodo['condicion']);
        
        // Validar operadores de comparación
        if (!preg_match('/(==|!=|<=|>=|<|>)/', $condicion)) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Operador de comparación inválido o faltante";
            return;
        }
        
        // Validar operandos
        $partes = preg_split('/(==|!=|<=|>=|<|>)/', $condicion);
        if (count($partes) !== 2) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Formato de condición inválido";
            return;
        }
        
        // Validar que los operandos sean válidos (variables o números)
        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (!preg_match('/^[a-zA-Z_]\w*$/', $parte) && // variable
                !preg_match('/^-?\d+$/', $parte)) {        // número
                $this->errores[] = "Error en línea {$nodo['linea']}: Operando inválido '$parte'";
            }
        }
    }

    private function validarFor($nodo) {
        if (!isset($nodo['inicializacion']) || !isset($nodo['condicion']) || !isset($nodo['incremento'])) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Estructura for incompleta";
            return;
        }

        // Validar inicialización
        if (!preg_match('/(int|double|float)\s+(\w+)\s*=\s*(.+)/', $nodo['inicializacion'])) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Inicialización de for inválida";
            return;
        }

        // Validar condición
        if (!preg_match('/(\w+)\s*(<=|>=|<|>|==|!=)\s*(\d+)/', $nodo['condicion'])) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Condición de for inválida";
            return;
        }

        // Validar incremento
        if (!preg_match('/(\w+)(\+\+|\+=\s*\d+|=\s*\w+\s*\+\s*\d+)/', $nodo['incremento'])) {
            $this->errores[] = "Error en línea {$nodo['linea']}: Incremento de for inválido";
            return;
        }

        // Registrar la variable del for en la tabla de símbolos
        preg_match('/(int|double|float)\s+(\w+)\s*=\s*(.+)/', $nodo['inicializacion'], $matches);
        $this->tablaSímbolos[$this->alcanceActual]['variables'][$matches[2]] = [
            'tipo' => $matches[1],
            'valorInicial' => $matches[3],
            'línea' => $nodo['linea'],
            'esVariableFor' => true
        ];
    }

    public function obtenerResultadoHTML() {
        $html = '<div class="mt-4">';
        $html .= '<h4>Análisis Semántico</h4>';
        
        // Tabla de símbolos
        $html .= '<h5 class="mt-3">Tabla de Símbolos</h5>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped table-bordered">';
        $html .= '<thead><tr><th>Alcance</th><th>Nombre</th><th>Tipo</th><th>Detalles</th></tr></thead><tbody>';
        
        foreach ($this->tablaSímbolos as $alcance => $info) {
            // Mostrar variables
            foreach ($info['variables'] as $nombre => $var) {
                $html .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>Variable</td><td>Tipo: %s, Línea: %d</td></tr>',
                    htmlspecialchars($alcance),
                    htmlspecialchars($nombre),
                    htmlspecialchars($var['tipo']),
                    $var['línea']
                );
            }
            
            // Mostrar métodos
            foreach ($info['métodos'] as $nombre => $metodo) {
                $parametrosStr = '';
                foreach ($metodo['parametros'] as $param) {
                    $parametrosStr .= $param['tipo'] . ' ' . $param['nombre'] . ', ';
                }
                $parametrosStr = rtrim($parametrosStr, ', ');
                
                $html .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>Método</td><td>Retorno: %s, Parámetros: (%s), Línea: %d</td></tr>',
                    htmlspecialchars($alcance),
                    htmlspecialchars($nombre),
                    htmlspecialchars($metodo['tipoRetorno']),
                    htmlspecialchars($parametrosStr),
                    $metodo['línea']
                );
            }
        }
        
        $html .= '</tbody></table></div>';
        
        // Mostrar errores si existen
        if (!empty($this->errores)) {
            $html .= '<h5 class="mt-3">Errores Semánticos</h5>';
            $html .= '<div class="alert alert-danger">';
            $html .= '<ul class="mb-0">';
            foreach ($this->errores as $error) {
                $html .= '<li>' . htmlspecialchars($error) . '</li>';
            }
            $html .= '</ul></div>';
        }
        
        $html .= '</div>';
        return $html;
    }
} 