<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tên bảng (nếu khác với tên mặc định).
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * Các trường có thể được gán giá trị hàng loạt.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'ordinal',
        'is_active',
    ];

    /**
     * Các kiểu dữ liệu cần được cast tự động.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'ordinal' => 'integer',
    ];

    /**
     * Quan hệ: Danh mục cha.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Quan hệ: Danh mục con.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
