<?php

namespace OptimistDigital\NovaPageManager\Models;

use OptimistDigital\NovaPageManager\NovaPageManager;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Page extends TemplateModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(NovaPageManager::getPagesTableName());
    }

    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($template) {
            // Is a parent template
            if ($template->parent_id === null) {
                // Find child templates
                $childTemplates = Page::where('parent_id', '=', $template->id)->get();
                if (count($childTemplates) === 0) return;

                // Set their parent to null
                $childTemplates->each(function ($template) {
                    $template->update(['parent_id' => null]);
                });
            }
        });

        static::saving(function ($page) {
            if (isset($page->draft) && NovaPageManager::draftEnabled()) {
                unset($page['draft']);
                return Page::createDraft($page);
            }
            
            return true;
        });
    }

    private static function createDraft($pageData) {
        if (isset($pageData->id)) {
            $newPage = $pageData->replicate();
            $newPage->published = false;
            $newPage->draft_parent_id = $pageData->id;
            $newPage->preview_token = Str::random(20);
            $newPage->save();
            return false;
        } 
        
        $pageData->published = false;
        $pageData->preview_token = Str::random(20);
        return true;
    }
    
    public function parent()
    {
        return $this->belongsTo(Page::class);
    }

    public function draftParent() 
    {
        return $this->belongsTo(Page::class);
    }

    public function childDraft() {
        return $this->hasOne(Page::class, 'draft_parent_id', 'id');
    }

}
