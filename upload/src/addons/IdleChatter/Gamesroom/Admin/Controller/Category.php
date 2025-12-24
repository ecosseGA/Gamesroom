<?php

namespace IdleChatter\Gamesroom\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Category extends AbstractController
{
    public function actionIndex()
    {
        $categoryRepo = $this->getCategoryRepo();
        $categories = $categoryRepo->findCategoriesForList()->fetch();
        
        $viewParams = [
            'categories' => $categories
        ];
        
        return $this->view('IdleChatter\Gamesroom:Category\List', 'gamesroom_category_list', $viewParams);
    }
    
    public function actionAdd()
    {
        $category = $this->em()->create('IdleChatter\Gamesroom:Category');
        return $this->categoryAddEdit($category);
    }
    
    public function actionEdit(ParameterBag $params)
    {
        $category = $this->assertCategoryExists($params->category_id);
        return $this->categoryAddEdit($category);
    }
    
    protected function categoryAddEdit(\IdleChatter\Gamesroom\Entity\Category $category)
    {
        $viewParams = [
            'category' => $category
        ];
        
        return $this->view('IdleChatter\Gamesroom:Category\Edit', 'gamesroom_category_edit', $viewParams);
    }
    
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        
        if ($params->category_id)
        {
            $category = $this->assertCategoryExists($params->category_id);
        }
        else
        {
            $category = $this->em()->create('IdleChatter\Gamesroom:Category');
        }
        
        $this->categorySaveProcess($category)->run();
        
        return $this->redirect($this->buildLink('gamesroom/categories'));
    }
    
    protected function categorySaveProcess(\IdleChatter\Gamesroom\Entity\Category $category)
    {
        $form = $this->formAction();
        
        $input = $this->filter([
            'title' => 'str',
            'description' => 'str',
            'icon' => 'str',
            'display_order' => 'uint',
            'active' => 'bool'
        ]);
        
        $form->basicEntitySave($category, $input);
        
        return $form;
    }
    
    public function actionDelete(ParameterBag $params)
    {
        $category = $this->assertCategoryExists($params->category_id);
        
        if ($this->isPost())
        {
            $category->delete();
            return $this->redirect($this->buildLink('gamesroom/categories'));
        }
        else
        {
            $viewParams = [
                'category' => $category
            ];
            
            return $this->view('IdleChatter\Gamesroom:Category\Delete', 'gamesroom_category_delete', $viewParams);
        }
    }
    
    protected function assertCategoryExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('IdleChatter\Gamesroom:Category', $id, $with, $phraseKey);
    }
    
    protected function getCategoryRepo()
    {
        return $this->repository('IdleChatter\Gamesroom:Category');
    }
}
