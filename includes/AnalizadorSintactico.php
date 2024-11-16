<?php
class AnalizadorSintactico {
    private $tokens;
    private $posicionActual = 0;
    private $arbolSintactico = [];

    public function __construct($tokens) {
        $this->tokens = $tokens;
    }

    public function analizar() {
        while ($this->posicionActual < count($this->tokens)) {
            $this->analizarDeclaracion();
        }
        return $this->arbolSintactico;
    }

    private function analizarDeclaracion() {
        $token = $this->tokens[$this->posicionActual];
        
        if ($token['tipo'] === 'palabra_reservada') {
            switch ($token['valor']) {
                case 'class':
                    $this->analizarClase();
                    break;
                case 'public':
                case 'private':
                case 'protected':
                    $this->analizarMetodo();
                    break;
                default:
                    $this->analizarVariable();
            }
        }
        
        $this->posicionActual++;
    }

    private function analizarClase() {
        $nodo = ['tipo' => 'clase'];
        $this->posicionActual++;
        
        if ($this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
            $nodo['nombre'] = $this->tokens[$this->posicionActual]['valor'];
        }
        
        $this->arbolSintactico[] = $nodo;
    }

    private function analizarMetodo() {
        $nodo = [
            'tipo' => 'metodo',
            'modificador' => $this->tokens[$this->posicionActual]['valor'],
            'parametros' => [],
            'linea' => $this->tokens[$this->posicionActual]['linea']
        ];
        
        $this->posicionActual++; // Avanzar después del modificador (public/private/protected)
        
        // Buscar static si existe
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['valor'] === 'static') {
            $nodo['esStatic'] = true;
            $this->posicionActual++;
        }
        
        // Obtener tipo de retorno
        if ($this->posicionActual < count($this->tokens)) {
            $nodo['tipoRetorno'] = $this->tokens[$this->posicionActual]['valor'];
            $this->posicionActual++;
        }
        
        // Obtener nombre del método
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
            $nodo['nombre'] = $this->tokens[$this->posicionActual]['valor'];
            $this->posicionActual++;
        }
        
        // Analizar parámetros si hay
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['valor'] === '(') {
            $this->posicionActual++; // Saltar el paréntesis de apertura
            
            while ($this->posicionActual < count($this->tokens) && 
                   $this->tokens[$this->posicionActual]['valor'] !== ')') {
                if ($this->tokens[$this->posicionActual]['tipo'] === 'palabra_reservada') {
                    $parametro = [
                        'tipo' => $this->tokens[$this->posicionActual]['valor']
                    ];
                    $this->posicionActual++;
                    
                    if ($this->posicionActual < count($this->tokens) && 
                        $this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
                        $parametro['nombre'] = $this->tokens[$this->posicionActual]['valor'];
                        $nodo['parametros'][] = $parametro;
                    }
                }
                $this->posicionActual++;
            }
        }
        
        $this->arbolSintactico[] = $nodo;
    }

    private function analizarVariable() {
        $nodo = [
            'tipo' => 'variable',
            'tipoVariable' => $this->tokens[$this->posicionActual]['valor'],
            'linea' => $this->tokens[$this->posicionActual]['linea']
        ];
        
        $this->posicionActual++; // Avanzar después del tipo
        
        // Obtener nombre de la variable
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
            $nodo['nombre'] = $this->tokens[$this->posicionActual]['valor'];
            $this->posicionActual++;
        }
        
        // Buscar inicialización si existe
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['valor'] === '=') {
            $this->posicionActual++; // Saltar el signo =
            
            if ($this->posicionActual < count($this->tokens)) {
                $nodo['valorInicial'] = $this->tokens[$this->posicionActual]['valor'];
            }
        }
        
        $this->arbolSintactico[] = $nodo;
    }

    public function obtenerResultadoHTML() {
        $html = '<div class="mt-4">';
        $html .= '<h4>Análisis Sintáctico</h4>';
        $html .= '<div class="card"><div class="card-body">';
        $html .= '<pre>' . $this->generarArbolHTML($this->arbolSintactico) . '</pre>';
        $html .= '</div></div></div>';
        return $html;
    }

    private function generarArbolHTML($nodos, $nivel = 0) {
        $html = '';
        $indentacion = str_repeat('  ', $nivel);
        
        foreach ($nodos as $nodo) {
            $html .= $indentacion . '└─ ' . $nodo['tipo'];
            if (isset($nodo['nombre'])) {
                $html .= ': ' . $nodo['nombre'];
            }
            $html .= "\n";
        }
        
        return $html;
    }
} 