<?php
/**
 * @author lin <465382251@qq.com>
 * */

namespace Lin\Exchange\Api;

use Lin\Exchange\Interfaces\TraderInterface;
use Lin\Exchange\Config\Exchanges;

class Trader extends Base implements TraderInterface
{
    /**
     * 买
     * 统一必填参数
     * @param $data
     * _symbol   币种
     * _number  购买数量
     * *****************以上参数必填写
     * _price      当前价格    填写参数为：限价交易    不填写为：市价交易
     * _client_id  自定义ID
     *
     * _entry   true:开仓   false:平仓。
     *
     * @param $show 默认true 下单完成后执行查询
     *
     * *****************以上参数非必填写
     * @return [
     *      ***返回原始数据
     *
     *      ***返回自定义数据，带'_'下划线的是统一返回参数格式。
     *      _status=>NEW 进行中   PART_FILLED 部分成交   FILLED 完全成交  CANCELING:撤销中   CANCELLED 已撤销   FAILURE 下单失败
     *      _filled_qty=>已交易完成数量
     *      _price_avg=>已成交均价
     *      _filed_amount=>已成交总金额
     *      _order_id=>系统ID
     *      _client_id=>自定义ID
     * ]
     *
     * */
    function buy(array $data,bool $show=true){
        try {
            //print_r($data);
            $requestTrader=$this->map->requestTrader();

            $map=$requestTrader->buy($data);
            //print_r($map);//die;
            $result=$this->exchange->trader()->buy($map);
            //print_r($result);
            $trader=$this->map->responseTrader()->buy(['result'=>$result,'request'=>$data]);
            //print_r($trader);

            //如果交易默认完成，则不用再查询
            if(isset($trader['_status'])) {
                if(in_array($trader['_status'],['FAILURE'])) return ['_error'=>$trader,'_status'=>'FAILURE'];
                //if(in_array($trader['_status'],['CANCELLED'])) return $trader;
            }

            if(!$show) return $trader;

            //如果交易所返回订单不存在抛出异常
            if(empty($trader['_order_id'])) throw new \Exception('Buy Failed');

            //交易所是撮合交易，所以查询需要间隔时间    市价交易不需要等待查询
            if($requestTrader->order_type!='market'){
                sleep(Exchanges::$TRADER_SHOW_TIME);
            }

            //再次查询结果
            return $this->show([
                '_symbol'=>$data['_symbol'] ?? ($trader['_symbol'] ?? ''),

                '_order_id'=>$trader['_order_id'] ?? '',
                '_client_id'=>$trader['_client_id'] ?? '',
            ]);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    /**
     * 卖
     * 统一必填参数
     * @param $data
     * _symbol   币种
     * _number  购买数量
     * *****************以上参数必填写
     * _price      当前价格    填写参数为：限价交易    不填写为：市价交易
     * _client_id  自定义ID
     *
     * _entry   true:开仓   false:平仓。
     *
     * @param $show 默认true 下单完成后执行查询
     * *****************以上参数非必填写
     * @return [
     *      ***返回原始数据
     *
     *      ***返回自定义数据，带'_'下划线的是统一返回参数格式。
     *      _status=>NEW 进行中   PART_FILLED 部分成交   FILLED 完全成交  CANCELING:撤销中   CANCELLED 已撤销   FAILURE 下单失败
     *      _filled_qty=>已交易完成数量
     *      _price_avg=>已成交均价
     *      _filed_amount=>已成交总金额
     *      _order_id=>系统ID
     *      _client_id=>自定义ID
     * ]
     *
     * */
    function sell(array $data,bool $show=true){
        try {
            $requestTrader=$this->map->requestTrader();

            $map=$requestTrader->sell($data);
            //print_r($map);
            $result=$this->exchange->trader()->sell($map);
            //print_r($result);
            $trader=$this->map->responseTrader()->sell(['result'=>$result,'request'=>$data]);
            //print_r($trader);die;

            //如果交易默认完成，则不用再查询
            //bitmex 存在可能
            if(isset($trader['_status'])) {
                if(in_array($trader['_status'],['FAILURE'])) return ['_error'=>$trader,'_status'=>'FAILURE'];
                //if(in_array($trader['_status'],['CANCELLED'])) return $trader;
            }

            if(!$show) return $trader;

            //如果交易所返回订单不存在抛出异常
            if(empty($trader['_order_id'])) throw new \Exception('Sell Failed');

            //交易所是撮合交易，所以查询需要间隔时间   市价交易不需要等待查询
            if($requestTrader->order_type!='market'){
                sleep(Exchanges::$TRADER_SHOW_TIME);
            }

            //再次查询结果
            return $this->show([
                '_symbol'=>$data['_symbol'] ?? ($trader['_symbol'] ?? ''),

                '_order_id'=>$trader['_order_id'] ?? '',
                '_client_id'=>$trader['_client_id'] ?? '',
            ]);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    /**
      * 删除订单 即撤单
     * 请求参数
     * @param $data
     * '_order_id'   与  _client_id 必须有一个存在
     *  '_symbol'=>'',
     * *****************以上参数必填写
     * _order_id  第三方平台ID
     * _client_id  自定义ID
     *
     * @param $show 默认true 删除订单后执行查询
     * *****************以上参数非必填写
     *
     * @return [
     *      ***返回原始数据
     *
     *      ***返回自定义数据，带'_'下划线的是统一返回参数格式。
     *      _status=>NEW 进行中   PART_FILLED 部分成交   FILLED 完全成交  CANCELING:撤销中   CANCELLED 已撤销   FAILURE 下单失败
     *      _filled_qty=>已交易完成数量
     *      _price_avg=>已成交均价
     *      _filed_amount=>已成交总金额
     *      _order_id=>系统ID
     *      _client_id=>自定义ID
     * ]
     * */
    function cancel(array $data,bool $show=true){
        try {
            $map=$this->map->requestTrader()->cancel($data);
            //print_r($map);
            $result=$this->exchange->trader()->cancel($map);
            //print_r($result);
            $trader=$this->map->responseTrader()->cancel(['result'=>$result,'request'=>$data]);
            //print_r($trader);

            //如果交易默认完成，则不用再查询
            if(isset($trader['_status'])) {
                if(in_array($trader['_status'],['FAILURE'])) return ['_error'=>$trader,'_status'=>'FAILURE'];
                //if(in_array($trader['_status'],['CANCELLED'])) return $trader;
            }

            if(!$show) return $trader;

            //交易所是撮合交易，所以查询需要间隔时间
            sleep(Exchanges::$TRADER_SHOW_TIME);

            //再次查询结果
            return $this->show([
                '_symbol'=>$data['_symbol'] ?? ($trader['_symbol'] ?? ''),

                '_order_id'=>$trader['_order_id'] ?? '',
                '_client_id'=>$trader['_client_id'] ?? '',
            ]);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
    }

    /**
     *
     * */
    function update(array $data){

    }

    /**
     * 查询订单
     * 请求参数
     * '_order_id'   与  _client_id 必须有一个存在
     * '_symbol'=>'',
     * *****************以上参数必填写
     * _order_id  第三方平台ID
     * _client_id  自定义ID
     * *****************以上参数非必填写
     * @return [
     * _status=>-2,-1,0,1,2   '完成交易'=>1,'挂单中'=>0, '部分完成'=>2,'撤单'=>-1,'系统错误'=>-2,
     * _filled_qty  => 返回已经成功  成交的仓位
     * _price_avg=>已成交均价
     * _filed_amount=>已成交总金额
     * _order_id 订单ID
     * _client_id  自定义ID
     * ]
     * */
    function show(array $data){
        try {
            $map=$this->map->requestTrader()->show($data);
            //print_r($map);
            $result=$this->exchange->trader()->show($map);
            //print_r($result);
            $trader=$this->map->responseTrader()->show(['result'=>$result,'request'=>$data]);
            //print_r($trader);
            if(isset($trader['_status'])) {
                if(in_array($trader['_status'],['FAILURE'])) return ['_error'=>$trader];
            }

            return $trader;
        }catch (\Exception $e){
            return $this->error($e->getMessage(),'system error');
        }
    }

    /**
     *
     * */
    function showAll(array $data){

    }
}
