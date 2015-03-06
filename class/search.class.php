<?php
namespace NERDZ\Core;

use PDO;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class Search {

    private $user, $project;
    
    public function __construct() {
        $this->user = new User();
        $this->project = new Project();
    }    

    private function byName($contains, $limit, $project = false) {
       if($limit)
           $limit = Security::limitControl($limit, 20);

       if(!$project) {
           $table = 'users';
           $field = 'username';
           $fetcher = $this->user;
       } else {
           $table = 'groups';
           $field = 'name';
           $fetcher = $this->project;
       }

        if(!($stmt = Db::query(
            [
                'SELECT "'.$field.'" FROM "'.$table.'" u WHERE u.'.$field.' ILIKE :contains ORDER BY u.'.$field.' LIMIT '.$limit,
                [
                    ':contains' => "%{$contains}%"
                ]
            ],Db::FETCH_STMT)))
            return [];

        $elements = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $ret = [];
        foreach($elements as $u) {
            $ret[] = $fetcher->getBasicInfo($u);
        }
        return $ret;
    }

    public function Username($contains, $limit) {
        return $this->byName($contains, $limit);
    }

    public function ProjectName($contains, $limit) {
        return $this->byName($contains, $limit, true);
    }
}
