<?php

namespace App\Http\Traits;

trait GetInitials
{
   
        /**
         * Generate initials from a name
         *
         * @param string $name
         * @return string
         */
        public function generate(string $name) : string
        {
            $words = explode(' ', $name);
            //$initials = strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1)); //Create initials from ONLY the first and the last words/names
            $initials = strtoupper( substr( $words[0], 0, 1 ) );
            if (count($words) >= 2) {
                for ( $i = 1; $i < count($words); $i++ ) {
                    $initials = $initials . strtoupper( substr($words[$i], 0, 1) );
                }
                return $initials;
            }
            return $this->makeInitialsFromSingleWord($name);
        }

        /**
         * Make initials from a word with no spaces
         *
         * @param string $name
         * @return string
         */
        protected function makeInitialsFromSingleWord(string $name) : string
        {
            preg_match_all('#([A-Z]+)#', $name, $capitals);
            if (count($capitals[1]) >= 2) {
                return substr(implode('', $capitals[1]), 0, 2);
            }
            return strtoupper(substr($name, 0, 2));
        }
    
    
    /*
    public function getData($model)
    {
        // Fetch all the data according to model
        return $model::all();
    }
    */
}