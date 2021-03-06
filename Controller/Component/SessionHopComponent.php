<?php

/**
 * SessionHopComponent. Provides the ability to bind session TTL to an array of action or number of request hops
 *
 * Copyright (c) Angel S. Moreno (https://github.com/angelxmoreno)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Angel S. Moreno (https://github.com/angelxmoreno)
 * @link          https://github.com/angelxmoreno Angel S. Moreno
 * @package       Cake.Controller.Component
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('SessionComponent', 'Controller/Component');

/**
 * Example:
 * 
 *  Create the session var `User.data` and allow it to live only in the UsersController
 *  $this->SessionHop->write('User.data', array('name' => 'Angel S. Moreno'), array('request' => array('controller' => 'users')));
 * 
 *  Create the session var `User.data` and allow it to live only in the Users Plugin but only the UsersController and UserDetailsController
 *  $this->SessionHop->write('User.data', array('name' => 'Angel S. Moreno'), array('request' => array('plugin' => 'users', 'controller' => array('users' , 'user_details'))));
 * 
 *  Create the session var `User.data` and allow it to live only for 2 hops
 *  $this->SessionHop->write('User.data', array('name' => 'Angel S. Moreno'), array('hops' => 2));
 */
class SessionHopComponent extends SessionComponent {

    public $hop_var_name = 'SessionHop';
    
    public function initialize(Controller $controller) {
        parent::initialize($controller);
        $this->_grimReaper($controller);
    }

    public function write($name, $value = null, array $hop_control = array()) {
        if ($hop_control) {
            parent::write($this->hop_var_name . '.' . md5($name), array('name' => $name, 'hop_control' => $hop_control));
        }
        parent::write($name, $value);
    }
    
    protected function _grimReaper(Controller $controller){
        $death_list = parent::read($this->hop_var_name);
        if(count($death_list)){
            $request_params = $controller->request->params;
            foreach($death_list as $soul => $target){
                $allow_life = true;
                
                //controlling the session hops using the request array
                if(isset($target['hop_control']['request'])){
                    foreach($target['hop_control']['request'] as $param_name => $param_value){
                        if(
                                (is_string($param_value) && $request_params[$param_name] <> $param_value)
                                ||
                                (is_array($param_value) && !in_array($request_params[$param_name],$param_value))
                        ){
                            $allow_life = false;
                        }
                    }
                }
                
                //controlling the session hops using a maximum number of hops
                if(isset($target['hop_control']['hops'])){
                    //add 1 hop since this should be a new request
                    $current_hops = (isset($target['hop_control']['current_hops'])) ? $target['hop_control']['current_hops'] + 1 : 1;
                    if($target['hop_control']['hops'] < $current_hops){
                        $allow_life = false;
                    } else {
                        parent::write($this->hop_var_name . '.' . $soul .'.'.'hop_control.current_hops', $current_hops);
                    }
                }
                
                if(!$allow_life){
                    $this->delete($this->hop_var_name . '.' . $soul);
                    /*
                     * @TODO fix dangling keys. If $target['name'] is something like User.data, the User array stays in session.
                     * Perhaps we should itirate through the Session array and delete empty keys.
                     */
                    $this->delete($target['name']);
                }
            }
        }
    }

}
