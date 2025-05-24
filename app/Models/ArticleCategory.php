<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ArticleCategory
 * 
 * @property int $id
 * @property int $article
 * @property int $category
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class ArticleCategory extends Model
{
	protected $table = 'article_category';

	protected $casts = [
		'article' => 'int',
		'category' => 'int'
	];

	protected $fillable = [
		'article',
		'category'
	];

	public function article()
	{
		return $this->belongsTo(Article::class, 'article');
	}

	public function category()
	{
		return $this->belongsTo(Category::class, 'category');
	}
}
