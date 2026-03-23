<?php

namespace App\Controllers;

class ApiController extends BaseController
{
    public function googleFontsSearch()
    {
        $query = $this->request->getGet('q');
        
        if (strlen($query) < 2) {
            return $this->response->setJSON(['fonts' => []]);
        }

        // Lista de fontes populares do Google Fonts (simplificada)
        $allFonts = [
            ['family' => 'Roboto', 'variants' => ['400', '700', '900']],
            ['family' => 'Open Sans', 'variants' => ['400', '700']],
            ['family' => 'Montserrat', 'variants' => ['400', '700', '900']],
            ['family' => 'Lato', 'variants' => ['400', '700']],
            ['family' => 'Raleway', 'variants' => ['400', '700', '900']],
            ['family' => 'Poppins', 'variants' => ['400', '700']],
            ['family' => 'Inter', 'variants' => ['400', '700']],
            ['family' => 'Playfair Display', 'variants' => ['400', '700', '900']],
            ['family' => 'Merriweather', 'variants' => ['400', '700']],
            ['family' => 'Ubuntu', 'variants' => ['400', '700']],
            ['family' => 'Nunito', 'variants' => ['400', '700', '900']],
            ['family' => 'Oswald', 'variants' => ['400', '700']],
            ['family' => 'Dosis', 'variants' => ['400', '700']],
            ['family' => 'Quicksand', 'variants' => ['400', '700']],
            ['family' => 'Cabin', 'variants' => ['400', '700']],
        ];

        // Filtrar fontes que correspondem à busca
        $results = array_filter($allFonts, function($font) use ($query) {
            return stripos($font['family'], $query) !== false;
        });

        return $this->response->setJSON(['fonts' => array_values($results)]);
    }
}
