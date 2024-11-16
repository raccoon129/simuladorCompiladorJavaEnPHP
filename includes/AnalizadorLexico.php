<?php
class AnalizadorLexico {
    private $codigoFuente;
    private $tokens = [];
    private $palabrasReservadas = [
        'public', 'private', 'protected', 'class', 'static', 'void', 
        'int', 'String', 'boolean', 'double', 'if', 'else', 'while', 'for'
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
        $palabras = preg_split('/([;{}\(\)=\s+])/', trim($linea), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (empty($palabra)) continue;

            if (in_array($palabra, $this->palabrasReservadas)) {
                $this->tokens[] = [
                    'tipo' => 'palabra_reservada',
                    'valor' => $palabra,
                    'linea' => $numeroLinea
                ];
            } elseif (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $palabra)) {
                $this->tokens[] = [
                    'tipo' => 'identificador',
                    'valor' => $palabra,
                    'linea' => $numeroLinea
                ];
            } elseif (preg_match('/^".*"$/', $palabra)) {
                $this->tokens[] = [
                    'tipo' => 'cadena',
                    'valor' => $palabra,
                    'linea' => $numeroLinea
                ];
            } elseif (preg_match('/^[0-9]+$/', $palabra)) {
                $this->tokens[] = [
                    'tipo' => 'numero',
                    'valor' => $palabra,
                    'linea' => $numeroLinea
                ];
            } elseif (in_array($palabra, [';', '{', '}', '(', ')', '='])) {
                $this->tokens[] = [
                    'tipo' => 'simbolo',
                    'valor' => $palabra,
                    'linea' => $numeroLinea
                ];
            }
        }
    }

    public function obtenerResultadoHTML() {
        $html = '<div class="table-responsive">';
        $html .= '<h4>Análisis Léxico</h4>';
        $html .= '<table class="table table-striped table-bordered">';
        $html .= '<thead><tr><th>Tipo</th><th>Valor</th><th>Línea</th></tr></thead><tbody>';
        
        foreach ($this->tokens as $token) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%d</td></tr>',
                htmlspecialchars($token['tipo']),
                htmlspecialchars($token['valor']),
                $token['linea']
            );
        }
        
        $html .= '</tbody></table></div>';
        return $html;
    }
} 