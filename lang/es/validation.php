<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser al menos :min.'],
    'max' => ['string' => 'El campo :attribute no debe superar los :max caracteres.', 'numeric' => 'El campo :attribute no debe ser mayor a :max.', 'file' => 'El archivo :attribute no debe superar los :max KB.'],
    'numeric' => 'El campo :attribute debe ser un número.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'in' => 'El valor de :attribute no es válido.',
    'image' => 'El archivo :attribute debe ser una imagen.',
    'mimes' => 'El archivo :attribute debe ser de tipo: :values.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'exists' => 'El valor de :attribute no es válido.',
    'gt' => ['numeric' => 'El campo :attribute debe ser mayor a :value.'],
    'attributes' => [
        'nombre' => 'nombre', 'documento' => 'carnet/NIT', 'telefono' => 'teléfono', 'email' => 'correo',
        'password' => 'contraseña', 'codigo' => 'código', 'categoria' => 'categoría', 'precio' => 'precio',
        'stock' => 'stock', 'imagen' => 'imagen', 'cliente_id' => 'cliente', 'fecha_entrega' => 'fecha de entrega',
        'producto_id' => 'producto', 'cantidad' => 'cantidad', 'adelanto' => 'adelanto', 'rol' => 'rol',
    ],
];
