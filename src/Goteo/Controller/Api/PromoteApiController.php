<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Goteo\Application\Exception\ControllerAccessDeniedException;
use Goteo\Application\Exception\ModelNotFoundException;
use Goteo\Application\Message;
use Goteo\Application\AppEvents;
use Goteo\Application\Config;

use Goteo\Model\Promote;
use Goteo\Model\Project;
use Goteo\Library\Text;
use Goteo\Library\Check;

class PromoteApiController extends AbstractApiController {

    protected function validatePromote($id) {

        if(!$this->user)
            throw new ControllerAccessDeniedException();

        $promote = $id ? Promote::get($id) : new Promote();

        if(!$promote)
            throw new ModelNotFoundException();

        if($this->user->hasPerm('admin-module-promote') ) {
            return $promote;
        }

        throw new ControllerAccessDeniedException(Text::get('admin-promote-not-active-yet'));
    }
    
    public function promoteSortAction($id, Request $request) {
        $promote = $this->validatePromote($id);

        $result = ['value' => (int)$promote->order, 'error' => false];

        if($request->isMethod('put') && $request->request->has('value')) {

            $res = Check::reorder($id, $request->request->get('value'), 'promote', 'id', 'order', ['node' => Config::get('node')]);

            if($res != $result['value']) {
                $result['value'] = $res;
            } else {
                $result['error'] = true;
                $result['message'] = 'Sorting failed';
            }
        }

        return $this->jsonResponse($result);
    }

    public function promoteAddAction(Request $request) {

        if(!$this->user && !$this->user->hasPerm('admin-module-promote') )
            throw new ControllerAccessDeniedException();

        $result = [];
        
        if($request->isMethod('post') && $request->request->has('value')) {
            $project = Project::get($request->request->get('value'));

            $data = array(
                'node' => Config::get('node'),
                'project' => $project->id,
                'order' => 0,  
                'active' => 1 
            );

            $promo = new Promote($data);

            if ($promo->save($errors)) {
                Check::reorder($promo->id, -1, 'promote', 'id', 'order', ['node' => Config::get('node')]);
            }
            else {
                Message::error(implode(', ', $errors));
            }

        }
        return $this->jsonResponse($promo);
    }			
    
    public function promotePropertyAction($id, $prop, Request $request) {
        $promote = $this->validatePromote($id);

        if(!$promote) throw new ModelNotFoundException();

        if($request->isMethod('put') && $request->request->has('value')) {

            if(!$this->user || !$this->user->hasPerm('admin-module-promote'))
                throw new ControllerAccessDeniedException();

            $promote->{$prop} = $request->request->get('value');

            if($promote->{$prop} == 'false') $promote->{$prop} = false;
            if($promote->{$prop} == 'true') $promote->{$prop} = true;
            $promote->{$prop} = (bool) $promote->{$prop};

            // do the SQL update
            $promote->dbUpdate([$prop]);
            $result['value'] = $promote->{$prop};
            if($errors = Message::getErrors()) {
                $result['error'] = true;
                $result['message'] = implode("\n", $errors);
            }
            if($messages = Message::getMessages()) {
                $result['message'] = implode("\n", $messages);
            }

        }
        return $this->jsonResponse($result);
    }

}