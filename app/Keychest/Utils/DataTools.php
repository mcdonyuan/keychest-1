<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 09.06.17
 * Time: 17:03
 */

namespace App\Keychest\Utils;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Traversable;

/**
 * Minor static collection utilities.
 *
 * Class DataTools
 * @package App\Keychest\Utils
 */
class DataTools {

    /**
     * Inverts collection mapping / inverts multi-map
     * watch id -> [certs] mapping turns into cert -> [watches]
     *
     *  k1 => [v1, v2]
     *  k2 => [v1, v3, v4]
     * to
     *  v1 => [k1, k2]
     *  v2 => [k1]
     *  v3 => [k2]
     *  v4 => [k2]
     *
     * @param Collection $map
     * @param bool $asCol
     * @return Collection
     */
    public static function invertMap($map, $asCol=false){
        $inverted = collect();
        $map->map(function($item, $key) use ($inverted, $asCol){
            $nitem = $item;
            if (!is_array($nitem) && !($item instanceof Traversable)){
                $nitem = [$nitem];
            }

            foreach($nitem as $cur){
                $submap = $inverted->get($cur, $asCol ? collect() : array());
                if ($asCol || $submap instanceof Collection){
                    $submap->push($key);
                } else {
                    $submap[] = $key;
                }
                $inverted->put($cur, $submap);
            }
        });
        return $inverted;
    }

    /**
     * Appends to an list within the collection.
     * @param array|Collection $col
     * @param mixed $key
     * @param mixed $val
     * @param bool $asCol
     * @return array|Collection
     */
    public static function appendTo($col, $key, $val, $asCol=true){
        if ($col instanceof Collection){
            if ($col->has($key)){
                $x = $col->get($key);
                if ($asCol){
                    $x->push($val);
                } else {
                    $x[] = $val;
                }
            } else {
                $col->put($key, $asCol ? collect([$val]) : [$val]);
            }

        } else {  // array
            if (array_key_exists($key, $col)){
                if ($asCol){
                    $col[$key]->push($val);
                } else {
                    $col[$key][] = $val;
                }
            } else {
                $col[$key] = $asCol ? collect([$val]) : [$val];
            }
        }

        return $col;
    }

    /**
     * Appends a value to the collection.
     * @param $col
     * @param $val
     * @return Collection
     */
    public static function appendToCol($col, $val){
        if (empty($col)){
            return collect([$val]);
        }

        $col->push($val);
        return $col;
    }

    /**
     * Transforms [key=>collect([val1, val2])] to [key=>[val1, val2]]
     * @param Collection $col
     * @return mixed
     */
    public static function multiListToArray($col){
        if (empty($col)){
            return $col;
        }

        return $col->map(function ($item, $key) {
            return ($item instanceof Collection) ? $item->all() : [];
        });
    }

    /**
     * Transforms [key=>[val1, val2]] to [key=>collect([val1, val2])]
     * @param $col
     * @return mixed
     */
    public static function muliListToCollection($col){
        if (empty($col)){
            return $col;
        }

        return $col->map(function ($item, $key) {
            return collect($item);
        });
    }

    /**
     * Creates a multimap from the collection by the $callback.
     * Function has to return [$key => $val].
     *
     * @param Collection $col
     * @param \Closure $callback
     * @param bool $mergeResult if true returned value is merged if multiple values are returned (arr / collection)
     * @return Collection
     */
    public static function multiMap($col, $callback, $mergeResult=false){
        $result = collect();

        $col->map(function($item, $key) use ($result, $callback, $mergeResult) {
            $pair = $callback($item, $key);
            foreach($pair as $nkey => $nval) {
                if ($mergeResult && (is_array($nval) || ($nval instanceof Traversable))){
                    foreach($nval as $nnval) {
                        self::appendTo($result, $nkey, $nnval, true);
                    }
                } else {
                    self::appendTo($result, $nkey, $nval, true);
                }
            }
        });

        return $result;
    }

    /**
     * In-place union to dst
     * @param Collection $dst
     * @param Collection $src
     * @return Collection
     */
    public static function addAll($dst, $src){
        $src->each(function ($item, $key) use ($dst) {
            $dst->push($item);
        });
        return $dst;
    }

    /**
     * Returns subset of the collection / map by the given keys.
     * Could be implemented as a filter on the key. Syntactic sugar.
     * @param $col
     * @param $ids
     * @return Collection
     */
    public static function pick($col, $ids){
        $idsArr = is_array($ids) || $ids instanceof Traversable;
        if (!$idsArr){
            $ids = [$ids];
        }

        $idSet = array_fill_keys(($ids instanceof Collection) ? $ids->values()->all() : $ids, true);
        $ret = [];

        foreach($col as $key => $val){
            if (array_key_exists($key, $idSet)){
                $ret[$key] = $val;
            }
        }

        return collect($ret);
    }

    /**
     * Processes sort string produced by Vue table, returns parsed result for query.
     * @param $sort
     * @return Collection
     */
    public static function vueSortToDb($sort){
        if (empty(trim($sort))){
            return collect();
        }

        $sorts = collect(explode(',', $sort));
        return $sorts->map(function($item, $key){
            $asc = true;
            if (strpos($item, '|') !== false){
                list($item, $ordtxt) = explode('|', $item, 2);
                $asc = $ordtxt == 'asc';
            }
            return [$item, $asc];
        })->values();
    }

    /**
     * Simple comparator on arrays.
     * Lexicographic comparator
     * @param $a
     * @param $b
     * @return int
     */
    public static function compareArrays($a, $b){
        $cA = count($a);
        $cB = count($b);
        $cMin = min($cA, $cB);

        for($i=0; $i < $cMin; $i++){
            $aa = $a[$i];
            $bb = $b[$i];
            $res = 0;
            if (is_numeric($aa) && is_numeric($bb)){
                $res = $aa - $bb;
            } else {
                $res = strnatcmp($aa, $bb);
            }

            if ($res != 0){
                return $res;
            }
        }

        return $cA - $cB;
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function useAsCallable($value)
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  string  $value
     * @return callable
     */
    public static function valueRetriever($value)
    {
        if (is_null($value)){
            return function ($item) {
                return $item;
            };
        }

        if (self::useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Sanitizes the timezone with carbon.
     * @param $timezone
     * @return string
     */
    public static function sanitizeTimezone($timezone){
        try{
            Carbon::now($timezone);
            return $timezone;

        } catch(Exception $e){
            return 'UTC';
        }
    }

    /**
     * Shortens large strings before dump.
     * @param $input
     * @return mixed
     */
    public static function shortenBeforeDump($input){
        if (is_array($input)){
            $newArr = [];
            foreach ($input as $key => $value) {
                $newArr[$key] = self::shortenBeforeDump($value);
            }
            return $newArr;

        } elseif (is_string($input)){
            $ln = strlen($input);
            if ($ln < 300){
                return $input;
            }

            return substr($input, 0, 150)
                . ' ... ('.($ln - 300).') ...'
                . substr($input, -150);

        } else {
            return $input;
        }
    }

    /**
     * Transform the arrays to objects.
     * Only associative arrays are converted to objects.
     * @param $val
     * @return array|Collection|object
     */
    public static function asObject($val){
        if ($val instanceof Collection){
            return $val->map(function($item, $idx){
                return self::asObject($item);
            });

        } else if (is_array($val) && Arr::isAssoc($val)){
            return (object) $val;

        }
        return $val;
    }

    /**
     * Recursively transform the arrays to objects.
     * Only associative arrays are converted to the objects.
     *
     * @param $val
     * @return object|static
     */
    public static function asObjectRecursive($val){
        if ($val instanceof Collection){
            return $val->map(function($item, $idx){
                return self::asObject($item);
            });

        } else if (is_array($val)){
            $objConvFnc = function ($a) use ( &$objConvFnc ) {
                if (!is_array($a)){
                    return $a;
                }

                $mapped = array_map($objConvFnc, $a);
                return Arr::isAssoc($a) ? (object) $mapped : $mapped;
            };
            return (object) array_map($objConvFnc, $val);

        }
        return $val;
    }

    /**
     * Capitalizes first word if is all in the same case
     * TERENA -> Terena
     * terena -> Terena
     * cloudFlare -> cloudFlare
     * @param str
     * @returns {string}
     */
    public static function capitalizeFirstWord($str){
        $r = preg_replace_callback('/^([A-Z]+)\b/', function($m) {
            return isset($m[1]) ? ucfirst(strtolower($m[1])) : '';
        }, $str);

        return preg_replace_callback('/^([a-z]+)\b/', function($m) {
            return isset($m[1]) ? ucfirst($m[1]) : '';
        }, $r);
    }

    /**
     * @param $data
     * @param $col
     * @param null $normalizer
     * @param null $newField
     * @return Collection
     */
    public static function normalizeValue($data, $col, $normalizer=null, $newField=null){
        $newField = $newField ?: $col.'_norm';
        $normalizer = $normalizer ?: 'DataTools::capitalizeFirstWord';
        $data = collect($data);

        $vals = $data->pluck($col);

        // group by normalized striped form of the field
        $grps = $vals->groupBy(function($x){
            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $x));
        });

        // find the representative in the group - group by on subgroups, most frequent subgroup wins
        $subg = $grps->map(function($item, $key){
            // a,a,a,a, b,b,b -> a: [a,a,a,a], b: [b,b,b]
            return $item->groupBy(function($x){
                return $x;
            })->mapWithKeys(function($item, $key){
                return [$key => count($item)];
            });
        });

        // map back all variants of the field to the normalized key - used for normalization
        $normMap = collect();
        foreach ($subg as $key => $val){
            foreach ($val as $sample => $x) {
                $normMap[$sample] = $key;
            }
        }

        // mapped -> representant
        $repr = $subg->map(function($x, $key) {
            if (count($x) === 1){
                return $x->keys()[0];
            }

            return $x->keys()->reduce(function($acc, $key) use ($x){
                if (!$acc){
                    return $key;
                }
                return $x[$key] > $x[$acc] ? $key : $acc;
            });
        });

        // a bit of polishing of the representants
        $frep = $repr->map(function($item, $key) use ($normalizer){
            return $normalizer($item);
        });

        // normalization step -> add a new field
        return $data->map(function($item, $key) use ($col, $newField, $frep, $normMap) {
            $curField = is_callable($col) ? $col($item) : $item[$col];
            $item[$newField] = $frep[$normMap[$curField]];
            return $item;
        });

    }
}
