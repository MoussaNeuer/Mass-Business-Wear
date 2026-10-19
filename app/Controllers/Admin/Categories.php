<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminCategoryRepository;

/** CRUD catégories admin. */
final class Categories extends BaseAdmin
{
    public function index(): never
    {
        self::render('admin/categories/index', [
            'categories' => AdminCategoryRepository::all(),
        ], 'Catégories');
    }

    public function store(): never
    {
        $data = $this->validate();
        if (AdminCategoryRepository::slugExists($data['slug'])) {
            self::backError('Ce slug existe déjà.');
        }
        $id = AdminCategoryRepository::create($data);
        self::log('category.create', $data['name'] . ' (#' . $id . ')');
        self::redirect(Url::admin('categories'), 'Catégorie créée.');
    }

    public function update(string $id): never
    {
        $id = (int) $id;
        $cat = AdminCategoryRepository::find($id);
        if ($cat === null) {
            Response::abort(404);
        }
        $data = $this->validate();
        if (AdminCategoryRepository::slugExists($data['slug'], $id)) {
            self::backError('Ce slug est déjà utilisé.');
        }
        AdminCategoryRepository::update($id, $data);
        self::log('category.update', $data['name'] . ' (#' . $id . ')');
        self::redirect(Url::admin('categories'), 'Catégorie mise à jour.');
    }

    public function destroy(string $id): never
    {
        $id = (int) $id;
        $cat = AdminCategoryRepository::find($id);
        if ($cat === null) {
            Response::abort(404);
        }
        AdminCategoryRepository::delete($id);
        self::log('category.delete', $cat['name'] . ' (#' . $id . ')');
        self::redirect(Url::admin('categories'), 'Catégorie supprimée.');
    }

    /** @return array{name:string,slug:string,description:string,image:string,is_active:bool,sort_order:int} */
    private function validate(): array
    {
        $name = trim(Request::post('name', ''));
        if ($name === '' || mb_strlen($name) > 80) {
            self::backError('Nom obligatoire (80 caractères max).');
        }
        $slug = trim(Request::post('slug', ''));
        $slug = $slug !== '' ? Security::slugify($slug) : Security::slugify($name);
        if ($slug === '') {
            self::backError('Slug invalide.');
        }
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => trim(Request::post('description', '')),
            'image' => trim(Request::post('image', '')),
            'is_active' => Request::post('is_active') !== null,
            'sort_order' => Request::int('sort_order', 0),
        ];
    }
}
