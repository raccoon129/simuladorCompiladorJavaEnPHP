<?php
class AnalizadorLexico {
    private $codigoFuente;
    private $tokens = [];
    
    // Expandir palabras reservadas
    private $palabrasReservadas = [
        // Modificadores de acceso y otros modificadores
        'public', 'private', 'protected', 'static', 'final', 'abstract',
        
        // Tipos de datos
        'void', 'int', 'double', 'float', 'String', 'boolean', 'char', 'long', 'byte', 'short',
        
        // Estructuras de control
        'if', 'else', 'while', 'for', 'do', 'switch', 'case', 'break', 'continue', 'return', 'int',
        
        // Otros
        'class', 'new', 'try', 'catch', 'finally', 'throw', 'throws', 'null', 'true', 'false',
        'extends', 'implements', 'interface', 'package', 'import'
    ];

    // Operadores
    private $operadores = [
        // Aritméticos
        '+', '-', '*', '/', '%', '++', '--',
        
        // Relacionales
        '==', '!=', '>', '<', '>=', '<=',
        
        // Lógicos
        '&&', '||', '!',
        
        // Asignación
        '=', '+=', '-=', '*=', '/=', '%=',
        
        // Bits
        '&', '|', '^', '~', '<<', '>>', '>>>'
    ];

    public function __construct($codigoFuente) {
        $this->codigoFuente = $codigoFuente;
    }

    public function analizar() {
        $lineas = explode("\n", $this->codigoFuente);
        $numeroLinea = 1;
        
        foreach ($lineas as $linea) {
            $this->analizarLinea($linea, $numeroLinea);
            $numeroLinea++;
        }
        
        return $this->tokens;
    }

    private function analizarLinea($linea, $numeroLinea) {
        // Patrón mejorado para capturar todos los elementos
        $patron = '/([a-zA-Z_][a-zA-Z0-9_]*)|' .  // identificadores
                 '(\"[^\"]*\")|' .                 // cadenas
                 '(\d+\.\d+|\d+)|' .              // números (enteros y decimales)
                 '([\+\-\*\/\%\=\!\<\>\&\|\^\~\(\)\{\}\[\]\;\,\.])|' . // operadores y símbolos
                 '(==|!=|>=|<=)|' .               // operadores de comparación
                 '([\s\t]+)/';                    // espacios en blanco
        
        preg_match_all($patron, $linea, $coincidencias, PREG_OFFSET_CAPTURE);
        
        foreach ($coincidencias[0] as $coincidencia) {
            $valor = $coincidencia[0];
            if (trim($valor) === '') continue;

            // Determinar el tipo de token
            if (in_array($valor, $this->palabrasReservadas)) {
                $this->agregarToken('palabra_reservada', $valor, $numeroLinea);
            }
            elseif (in_array($valor, $this->operadores)) {
                $this->agregarToken('operador', $valor, $numeroLinea);
            }
            elseif (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $valor)) {
                $this->agregarToken('identificador', $valor, $numeroLinea);
            }
            elseif (preg_match('/^".*"$/', $valor)) {
                $this->agregarToken('cadena', $valor, $numeroLinea);
            }
            elseif (preg_match('/^\d+\.\d+$/', $valor)) {
                $this->agregarToken('decimal', $valor, $numeroLinea);
            }
            elseif (preg_match('/^\d+$/', $valor)) {
                $this->agregarToken('entero', $valor, $numeroLinea);
            }
            elseif (in_array($valor, [';', '{', '}', '(', ')', '[', ']', ',', '.'])) {
                $this->agregarToken('simbolo', $valor, $numeroLinea);
            }
            elseif (preg_match('/^\'.*\'$/', $valor)) {
                $this->agregarToken('caracter', $valor, $numeroLinea);
            }
        }
    }

    private function agregarToken($tipo, $valor, $linea) {
        $this->tokens[] = [
            'tipo' => $tipo,
            'valor' => $valor,
            'linea' => $linea
        ];
    }

    public function obtenerResultadoHTML() {
        $html = '<div class="table-responsive">';
        $html .= '<h4>Análisis Léxico</h4>';
        $html .= '<table class="table table-striped table-bordered">';
        $html .= '<thead><tr>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Línea</th>
                    <th>Descripción</th>
                </tr></thead><tbody>';
        
        foreach ($this->tokens as $token) {
            $descripcion = $this->obtenerDescripcionToken($token);
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td></tr>',
                htmlspecialchars($token['tipo']),
                htmlspecialchars($token['valor']),
                $token['linea'],
                htmlspecialchars($descripcion)
            );
        }
        
        $html .= '</tbody></table></div>';
        return $html;
    }

    private function obtenerDescripcionToken($token) {
        switch ($token['tipo']) {
            case 'palabra_reservada':
                return 'Palabra clave del lenguaje Java';
            case 'operador':
                return 'Operador ' . $this->obtenerTipoOperador($token['valor']);
            case 'identificador':
                return 'Nombre de variable, método o clase';
            case 'cadena':
                return 'Cadena de texto';
            case 'decimal':
                return 'Número decimal';
            case 'entero':
                return 'Número entero';
            case 'simbolo':
                return $this->obtenerDescripcionSimbolo($token['valor']);
            case 'caracter':
                return 'Carácter único';
            default:
                return 'Token no especificado';
        }
    }

    private function obtenerTipoOperador($operador) {
        $tipos = [
            '+' => 'de suma',
            '-' => 'de resta',
            '*' => 'de multiplicación',
            '/' => 'de división',
            '%' => 'módulo',
            '=' => 'de asignación',
            '==' => 'de igualdad',
            '!=' => 'de desigualdad',
            '>' => 'mayor que',
            '<' => 'menor que',
            '>=' => 'mayor o igual que',
            '<=' => 'menor o igual que',
            '&&' => 'AND lógico',
            '||' => 'OR lógico',
            '!' => 'NOT lógico'
        ];
        return $tipos[$operador] ?? 'no especificado';
    }

    private function obtenerDescripcionSimbolo($simbolo) {
        $simbolos = [
            ';' => 'Fin de sentencia',
            '{' => 'Inicio de bloque',
            '}' => 'Fin de bloque',
            '(' => 'Inicio de paréntesis',
            ')' => 'Fin de paréntesis',
            '[' => 'Inicio de arreglo',
            ']' => 'Fin de arreglo',
            ',' => 'Separador',
            '.' => 'Operador de acceso'
        ];
        return $simbolos[$simbolo] ?? 'Símbolo no especificado';
    }
} 