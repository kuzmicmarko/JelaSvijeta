<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Meal;
use App\Models\Tag;
use App\Models\Status;
use App\Models\Category;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use Astrotomic\Translatable\Translatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Validator;
use App\Interfaces\MealRepositoryInterface;
use App\Http\Resources\MealResource;
use App\Http\Resources\MealCollection;

class MealController extends Controller
{
    use Translatable;
    private MealRepositoryInterface $mealRepository;

    public function __construct(MealRepositoryInterface $mealRepository)
    {
        $this->mealRepository = $mealRepository;
    }

/**
     * Display the specified resource.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request){

        $rules = array(
            'per_page' => 'numeric|not_negative_integer',
            'tags' => 'non_negative_integer_array',
            'lang' => 'two_character_lang_tag',
            'with' => 'with_tag_false',
            'category' => 'not_negative_integer_or_null',
            'diff_time' => 'numeric|not_negative_integer',

        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        
        $per_page = $request->get('per_page');
        
        $lang = $request->get('lang');
        
        $tag_id = $request->get('tags');

        $with_keywords = $request->get('with');
        
        $with = explode(",", $with_keywords);

        $diffTime = $request->get('diff_time');

        $category_id = $request->get('category');

        if (!empty($lang))
            App::setLocale($lang);

        $query = Meal::query();

        if (empty($tag_id) && empty($with_keywords) && empty($diffTime) && empty($category_id)){
            $meals = $this->mealRepository->getAllMeals($per_page);
            $meals->appends($request->except('page'));
            return response()->json(new MealCollection($meals));
        }

        if (!empty($tag_id)){
            $query = $this->mealRepository->getMealsByTagId($query, $tag_id);
        }

        if (!empty($category_id)){
            $query = $this->mealRepository->filterByCategoryId($query, $category_id);    
        }

        if (!empty($diffTime)) {
            $query = $this->mealRepository->filterByDiffTime($query, $diffTime);
        }
        
        if(in_array('tags', $with)){
        $query = $this->mealRepository->loadTags($query, $with);
        }

        if(in_array('ingredients', $with)){
        $query = $this->mealRepository->loadIngredients($query, $with);    
        }

        if(in_array('category', $with)){
        $query = $this->mealRepository->loadCategories($query, $with);
        }
        
        $meals = $this->mealRepository->paginateMeals($query, $per_page);

        $meals->appends($request->except('page'));

        return response()->json(new MealCollection($meals));
    }

}
