<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NERDZ\Core;

require_once __DIR__.'/Autoload.class.php';

class OAuth2Client
{
    private $id;

    public function __construct($id = null)
    {
        if ($id !== null && is_numeric($id)) {
            $this->id = $id;
        }
    }

    public function getObject()
    {
        return Db::query(
            [
                'SELECT * FROM "oauth2_clients" WHERE "id" = :id',
                [
                    ':id' => $this->id,
                ],
            ],
            Db::FETCH_OBJ
        );
    }
}
