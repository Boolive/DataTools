<?php
/**
 * 
 * @author Vladimir Shestakov
 * @version 1.0
 */
namespace boolive\data_tools;

use boolive\basic\controller\controller;
use boolive\core\data\Data;
use boolive\core\data\Entity;
use boolive\core\develop\Trace;
use boolive\core\errors\Error;
use boolive\core\functions\F;
use boolive\core\request\Request;
use boolive\core\values\Rule;
use boolive\core\cli\CLI as C;

class data_tools extends controller
{
    function startRule()
    {
        return Rule::arrays([
            'REQUEST' => Rule::arrays([
                'method' => Rule::string()->eq('CLI')
            ]),
            'ARG' => Rule::arrays([
                1 => Rule::eq('data_tools')->required(),
                2 => Rule::string(),
                3 => Rule::string(),
                4 => Rule::string(),
                5 => Rule::string(),
            ])
            //'previous' => Rule::eq(false)
        ]);
    }

    function work(Request $request)
    {

        $uri = '';

//        C::writeln(Trace::format($_SERVER, false));
//        $s = C::read();
//        C::writeln(mb_detect_encoding($s,['utf-8','cp866']));
        do{
            try{
                C::write(C::style($uri, [C::COLOR_GRAY_DARK]).'> ');
                $commands = preg_split('/\s/ui', trim(C::read()));
                $cmd_count = count($commands);
                if ($commands[0] == 'show'){
                    $new_uri = !empty($commands[1])? $uri.'/'.trim($commands[1],' \/') : $uri;
                    $obj = Data::read($new_uri);
                    C::writeln(C::style(Trace::format($obj, false)));
                }else
                if (mb_strlen($commands[0]) > 1 && ($commands[0]{0} == '/' || mb_substr($commands[0],0,2)=='..')){
                    $new_uri = !empty($commands[0])? $uri.'/'.trim($commands[0],' \/') : $uri;
                    $obj = Data::read($new_uri, false, true);
                    if ($obj && $obj->is_exists()){
                        $uri = $obj->uri();
                    }else{
                        C::writeln(C::style('Object does not exist', C::COLOR_RED));
                    }
                }else
                if ($commands[0] == 'ls' || $commands[0] == 'children'){
                    $list = Data::find([
                        'from' => $uri,
                        'select' => empty($commands[1]) || $commands[1]!='-p'? 'children' : 'properties',
                        'struct' => 'list',
                        'limit' => [0,50]
                    ]);
                    foreach ($list as $obj){
                        C::writeln(C::style($obj->name(), C::COLOR_BLUE));
                    }
                }else
                if ($cmd_count > 1 && $commands[0] == 'find') {
                    $cond = empty($commands[1]) ? '' : trim($commands[1], '/');
                    $result = Data::find($cond);
                    C::write(C::style(Trace::format($result, false)));
                }else
                if ($commands[0] == 'color'){
                    if (!empty($commands[1])){
                        C::use_style($commands[1] == 'off'? false : null);
                    }else {
                        C::use_style(true);
                    }
                }else
                if ($cmd_count > 1 && $commands[0] == 'attr') {
                    $obj = Data::read($uri);
                    $attr = $commands[1];
                    if ($obj instanceof Entity && $obj->is_exists() && $obj->is_attr($attr)){
                        if ($cmd_count > 2){
                            if ($commands[2] === "null"){
                                $commands[2] = null;
                            }else{
                                $commands[2] = trim(trim($commands[2],'"'));
                            }
                            $obj->{$attr}($commands[2]);
                            Data::write($obj);
                            C::writeln(Trace::format($obj, false));
                        }else{
                            C::writeln(C::style($obj->attr($attr)), C::COLOR_PURPLE);
                        }
                    }
                }else
                if ($cmd_count > 2 && $commands[0] == 'new'){
                    $new_uri = !empty($commands[1])? $uri.'/'.trim($commands[1],' \/') : $uri;
                    list($parent, $name) = F::splitRight('/', $new_uri);
                    if (!$parent) $parent = '';
                    $proto = $commands[2];
                    $obj = Data::create($proto, $parent, ['name' => $name]);
                    $signs = array_flip($commands);
                    if (isset($signs['-m'])) $obj->is_mandatory(true);
                    if (isset($signs['-p'])) $obj->is_property(true);
                    if (isset($signs['-d'])) $obj->is_draft(true);
                    if (isset($signs['-h'])) $obj->is_hidden(true);
                    if (isset($signs['-l'])) $obj->is_link(true);
                    if (isset($signs['-r'])) $obj->is_relative(true);
                    if (!$obj->is_link()){
                        $obj->complete();
                    }
                    Data::write($obj);
                    C::writeln(Trace::format($obj, false));
                }

                else{
                    C::writeln(C::style('Unknown command', C::COLOR_RED));
                }
            }
            catch (Error $e){
                C::writeln(C::style($e->getMessage(), C::COLOR_RED));
            }
            catch (\Exception $e){
                C::writeln(C::style((string)$e, C::COLOR_RED));
            }
        }while($commands[0] !== 'exit');
        C::writeln("\nby!");

//        if (!empty($request['ARG'][2])) {
//            if ($request['ARG'][2] == 'read') {
//                $uri = empty($request['ARG'][3]) ? '' : '/' . trim($request['ARG'][3], '/');
//                $obj = Data::read($uri);
//                C::writeln(C::style(Trace::style($obj, false)));
//            } else
//            if ($request['ARG'][2] == 'find') {
//                $cond = empty($request['ARG'][3]) ? '' : trim($request['ARG'][3], '/');
//                $result = Data::find($cond);
//                C::write(C::style(Trace::style($result, false)));
//            } else
//            if ($request['ARG'][2] == 'edit') {
//                $object = Data::read(empty($request['ARG'][3]) ? '' : '/'.trim($request['ARG'][3], '/'));
//                $attr = empty($request['ARG'][4]) ? null : $request['ARG'][4];
//                $value = empty($request['ARG'][5]) ? null : $request['ARG'][5];
//                if ($object instanceof Entity && $object->is_exists() && $attr){
//                    $object->{$attr}($value);
//                    Data::write($object);
//                    C::write(C::style(Trace::style($object, false)));
//                }
//            }else
//            {
////        phpinfo();
////        C::writeln(Trace::style($_ENV, false));
////        C::writeln(getenv('ANSICON'));
//                C::writeln(
//                    C::style(json_encode($request['ARG']), [C::STYLE_BOLD, C::STYLE_UNDERLINE, C::COLOR_BLUE]));
//                //$a = C::read();
//                //C::writeln($a);
//
////        fwrite(STDOUT, $colors->getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n");
////        $line = fgets(STDIN);
////        if(trim($line) != 'yes'){
////            echo "ABORTING!\n";
////            exit;
////        }
////        echo "\n";
////        echo "Thank you, continuing...\n";
////        return true;
//            }
//        }
    }
}