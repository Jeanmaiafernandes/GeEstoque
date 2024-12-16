<?php

namespace App\Repositories;
use App\Models\Product;
use App\Interfaces\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    public function index(){
        return Product::all();
    }

    public function getById($id){
        return product::findOrFail($id);
    }

    public function store(array $data){
        return Product::create($data);
    }

    public function update(array $data, $id){
        return Product::whereId($id)->update($data);
    }

    public function delete($id){
        return Product::destroy($id);
    }
}
