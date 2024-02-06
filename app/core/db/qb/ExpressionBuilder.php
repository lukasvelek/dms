<?php

namespace QueryBuilder;

class ExpressionBuilder {
    private array $queryData;

    public function __construct() {
        $this->queryData = [];
    }

    public function and() {
        $this->queryData[] = 'AND';

        return $this;
    }

    public function or() {
        $this->queryData[] = 'OR';

        return $this;
    }

    public function lb() {
        $this->queryData[] = '(';

        return $this;
    }

    public function rb() {
        $this->queryData[] = ')';

        return $this;
    }

    public function where(string $cond, array $values = []) {
        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                die();
            }

            $search = [];

            for($i = 0; $i < ($count - 1); $i++) {
                $search[] = '?';
            }

            $tmp = [];
            foreach($values as $value) {
                $tmp[] = "'" . $value . "'";
            }

            $values = $tmp;

            $cond = str_replace($search, $values, $cond);
        }

        $this->queryData[] = $cond;

        return $this;
    }

    public function andWhere(string $cond, array $values = []) {
        $this->and();
        $this->where($cond, $values);

        return $this;
    }

    public function orWhere(string $cond, array $values = []) {
        $this->or();
        $this->where($cond, $values);

        return $this;
    }

    public function build() {
        $code = '';

        foreach($this->queryData as $qd) {
            $code .= ' ' . $qd . ' ';
        }

        return $code;
    }
}

?>