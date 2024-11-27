<?php
class AnalizadorSintactico {
    // Almacena los tokens que recibe del AnalizadorLexico
    private $tokens;
    // Mantiene la posición actual mientras recorre los tokens
    private $posicionActual = 0;
    // Almacena la estructura del programa en forma de árbol
    private $arbolSintactico = [];

    // Constructor: recibe los tokens del AnalizadorLexico
    public function __construct($tokens) {
        $this->tokens = $tokens;
    }

    // Método principal que inicia el análisis sintáctico
    public function analizar() {
        // Analiza todos los tokens hasta el final
        while ($this->posicionActual < count($this->tokens)) {
            $this->analizarDeclaracion();
        }
        // Retorna el árbol para que el AnalizadorSemantico lo procese
        return $this->arbolSintactico;
    }

    // Determina qué tipo de declaración es y la procesa
    private function analizarDeclaracion() {
        $token = $this->tokens[$this->posicionActual];
        
        // Si encuentra una palabra reservada, determina su contexto
        if ($token['tipo'] === 'palabra_reservada') {
            switch ($token['valor']) {
                case 'class':
                    // Procesa una declaración de clase
                    $this->analizarClase();
                    break;
                case 'public':
                case 'private':
                case 'protected':
                    // Procesa una declaración de método
                    $this->analizarMetodo();
                    break;
                default:
                    // Procesa una declaración de variable
                    $this->analizarVariable();
            }
        }
        
        // Avanza al siguiente token
        $this->posicionActual++;
    }

    // Analiza la estructura de una clase
    private function analizarClase() {
        // Crea un nodo tipo clase en el árbol
        $nodo = ['tipo' => 'clase'];
        $this->posicionActual++;
        
        // Obtiene el nombre de la clase
        if ($this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
            $nodo['nombre'] = $this->tokens[$this->posicionActual]['valor'];
        }
        
        // Agrega la clase al árbol sintáctico
        $this->arbolSintactico[] = $nodo;
    }

    // Analiza la estructura de un método
    private function analizarMetodo() {
        // Crea un nodo tipo método con sus propiedades
        $nodo = [
            'tipo' => 'metodo',
            'modificador' => $this->tokens[$this->posicionActual]['valor'],
            'parametros' => [],
            'linea' => $this->tokens[$this->posicionActual]['linea']
        ];
        
        $this->posicionActual++; // Avanza después del modificador
        
        // Verifica si el método es static
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['valor'] === 'static') {
            $nodo['esStatic'] = true;
            $this->posicionActual++;
        }
        
        // Obtiene el tipo de retorno del método
        if ($this->posicionActual < count($this->tokens)) {
            $nodo['tipoRetorno'] = $this->tokens[$this->posicionActual]['valor'];
            $this->posicionActual++;
        }
        
        // Obtiene el nombre del método
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
            $nodo['nombre'] = $this->tokens[$this->posicionActual]['valor'];
            $this->posicionActual++;
        }
        
        // Procesa los parámetros del método si existen
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['valor'] === '(') {
            $this->posicionActual++; // Salta el paréntesis de apertura
            
            // Analiza los parámetros hasta encontrar el paréntesis de cierre
            while ($this->posicionActual < count($this->tokens) && 
                   $this->tokens[$this->posicionActual]['valor'] !== ')') {
                if ($this->tokens[$this->posicionActual]['tipo'] === 'palabra_reservada') {
                    $parametro = [
                        'tipo' => $this->tokens[$this->posicionActual]['valor']
                    ];
                    $this->posicionActual++;
                    
                    // Obtiene el nombre del parámetro
                    if ($this->posicionActual < count($this->tokens) && 
                        $this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
                        $parametro['nombre'] = $this->tokens[$this->posicionActual]['valor'];
                        $nodo['parametros'][] = $parametro;
                    }
                }
                $this->posicionActual++;
            }
        }
        
        // Agrega el método al árbol sintáctico
        $this->arbolSintactico[] = $nodo;
    }

    // Analiza la estructura de una variable
    private function analizarVariable() {
        // Crea un nodo tipo variable con sus propiedades
        $nodo = [
            'tipo' => 'variable',
            'tipoVariable' => $this->tokens[$this->posicionActual]['valor'],
            'linea' => $this->tokens[$this->posicionActual]['linea']
        ];
        
        $this->posicionActual++; // Avanza después del tipo
        
        // Obtiene el nombre de la variable
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['tipo'] === 'identificador') {
            $nodo['nombre'] = $this->tokens[$this->posicionActual]['valor'];
            $this->posicionActual++;
        }
        
        // Procesa la inicialización de la variable si existe
        if ($this->posicionActual < count($this->tokens) && 
            $this->tokens[$this->posicionActual]['valor'] === '=') {
            $this->posicionActual++; // Salta el signo =
            
            // Obtiene el valor inicial
            if ($this->posicionActual < count($this->tokens)) {
                $nodo['valorInicial'] = $this->tokens[$this->posicionActual]['valor'];
            }
        }
        
        // Agrega la variable al árbol sintáctico
        $this->arbolSintactico[] = $nodo;
    }

    // Genera la representación HTML del análisis sintáctico
    public function obtenerResultadoHTML() {
        $html = '<div class="mt-4">';
        $html .= '<h4>Análisis Sintáctico</h4>';
        $html .= '<div class="card"><div class="card-body">';
        $html .= '<pre>' . $this->generarArbolHTML($this->arbolSintactico) . '</pre>';
        $html .= '</div></div></div>';
        return $html;
    }

    // Genera una representación visual del árbol sintáctico
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