<?php

/**
 * A helper file for Dcat Admin, to provide autocomplete information to your IDE
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author jqh <841324345@qq.com>
 */
namespace Dcat\Admin {
    use Illuminate\Support\Collection;

    /**
     * @property Grid\Column|Collection created_at
     * @property Grid\Column|Collection detail
     * @property Grid\Column|Collection id
     * @property Grid\Column|Collection name
     * @property Grid\Column|Collection type
     * @property Grid\Column|Collection updated_at
     * @property Grid\Column|Collection version
     * @property Grid\Column|Collection is_enabled
     * @property Grid\Column|Collection extension
     * @property Grid\Column|Collection icon
     * @property Grid\Column|Collection order
     * @property Grid\Column|Collection parent_id
     * @property Grid\Column|Collection uri
     * @property Grid\Column|Collection input
     * @property Grid\Column|Collection ip
     * @property Grid\Column|Collection method
     * @property Grid\Column|Collection path
     * @property Grid\Column|Collection user_id
     * @property Grid\Column|Collection menu_id
     * @property Grid\Column|Collection permission_id
     * @property Grid\Column|Collection http_method
     * @property Grid\Column|Collection http_path
     * @property Grid\Column|Collection slug
     * @property Grid\Column|Collection role_id
     * @property Grid\Column|Collection value
     * @property Grid\Column|Collection avatar
     * @property Grid\Column|Collection password
     * @property Grid\Column|Collection remember_token
     * @property Grid\Column|Collection username
     * @property Grid\Column|Collection banner
     * @property Grid\Column|Collection lang
     * @property Grid\Column|Collection sort
     * @property Grid\Column|Collection status
     * @property Grid\Column|Collection vedio
     * @property Grid\Column|Collection bigkey
     * @property Grid\Column|Collection num
     * @property Grid\Column|Collection content
     * @property Grid\Column|Collection content_en
     * @property Grid\Column|Collection content_fr
     * @property Grid\Column|Collection title_en
     * @property Grid\Column|Collection title_fr
     * @property Grid\Column|Collection element
     * @property Grid\Column|Collection help
     * @property Grid\Column|Collection key
     * @property Grid\Column|Collection rule
     * @property Grid\Column|Collection tab
     * @property Grid\Column|Collection depth
     * @property Grid\Column|Collection zhi_num
     * @property Grid\Column|Collection connection
     * @property Grid\Column|Collection exception
     * @property Grid\Column|Collection failed_at
     * @property Grid\Column|Collection payload
     * @property Grid\Column|Collection queue
     * @property Grid\Column|Collection uuid
     * @property Grid\Column|Collection insurance
     * @property Grid\Column|Collection is_redeem
     * @property Grid\Column|Collection multiple
     * @property Grid\Column|Collection next_time
     * @property Grid\Column|Collection ordernum
     * @property Grid\Column|Collection over_income
     * @property Grid\Column|Collection redeem_time
     * @property Grid\Column|Collection ticket_id
     * @property Grid\Column|Collection ticket_price
     * @property Grid\Column|Collection total_income
     * @property Grid\Column|Collection user_ticket_id
     * @property Grid\Column|Collection wait_income
     * @property Grid\Column|Collection coin_img
     * @property Grid\Column|Collection contract_address
     * @property Grid\Column|Collection contract_address_lp
     * @property Grid\Column|Collection is_success
     * @property Grid\Column|Collection is_sync
     * @property Grid\Column|Collection pancake_cate
     * @property Grid\Column|Collection precision
     * @property Grid\Column|Collection rate
     * @property Grid\Column|Collection gift_rank_id
     * @property Grid\Column|Collection gift_ticket_id
     * @property Grid\Column|Collection gift_ticket_num
     * @property Grid\Column|Collection lv
     * @property Grid\Column|Collection price
     * @property Grid\Column|Collection sales
     * @property Grid\Column|Collection static_rate
     * @property Grid\Column|Collection stock
     * @property Grid\Column|Collection hash
     * @property Grid\Column|Collection pay_type
     * @property Grid\Column|Collection finish_time
     * @property Grid\Column|Collection pay_status
     * @property Grid\Column|Collection pool
     * @property Grid\Column|Collection equal_rate
     * @property Grid\Column|Collection small_num
     * @property Grid\Column|Collection coin_type
     * @property Grid\Column|Collection date
     * @property Grid\Column|Collection main_chain
     * @property Grid\Column|Collection ticket_sale
     * @property Grid\Column|Collection is_del
     * @property Grid\Column|Collection is_fan
     * @property Grid\Column|Collection is_platform
     * @property Grid\Column|Collection symbol
     * @property Grid\Column|Collection total_price
     * @property Grid\Column|Collection group_num
     * @property Grid\Column|Collection new_parent_id
     * @property Grid\Column|Collection new_path
     * @property Grid\Column|Collection old_parent_id
     * @property Grid\Column|Collection old_path
     * @property Grid\Column|Collection new_wallet
     * @property Grid\Column|Collection old_wallet
     * @property Grid\Column|Collection day
     * @property Grid\Column|Collection ranking
     * @property Grid\Column|Collection reward
     * @property Grid\Column|Collection total
     * @property Grid\Column|Collection month
     * @property Grid\Column|Collection from_uid
     * @property Grid\Column|Collection insurance_id
     * @property Grid\Column|Collection source_type
     * @property Grid\Column|Collection cate
     * @property Grid\Column|Collection from_user_id
     * @property Grid\Column|Collection msg
     * @property Grid\Column|Collection code
     * @property Grid\Column|Collection headimgurl
     * @property Grid\Column|Collection hold_rank
     * @property Grid\Column|Collection is_valid
     * @property Grid\Column|Collection level
     * @property Grid\Column|Collection node_rank
     * @property Grid\Column|Collection rank
     * @property Grid\Column|Collection self_num
     * @property Grid\Column|Collection self_yeji
     * @property Grid\Column|Collection small_yeji
     * @property Grid\Column|Collection team_num
     * @property Grid\Column|Collection team_yeji
     * @property Grid\Column|Collection total_num
     * @property Grid\Column|Collection total_yeji
     * @property Grid\Column|Collection usdt
     * @property Grid\Column|Collection wallet
     * @property Grid\Column|Collection zhi_valid
     * @property Grid\Column|Collection ac_amount
     * @property Grid\Column|Collection fee
     * @property Grid\Column|Collection fee_amount
     * @property Grid\Column|Collection finsh_time
     * @property Grid\Column|Collection receive_address
     *
     * @method Grid\Column|Collection created_at(string $label = null)
     * @method Grid\Column|Collection detail(string $label = null)
     * @method Grid\Column|Collection id(string $label = null)
     * @method Grid\Column|Collection name(string $label = null)
     * @method Grid\Column|Collection type(string $label = null)
     * @method Grid\Column|Collection updated_at(string $label = null)
     * @method Grid\Column|Collection version(string $label = null)
     * @method Grid\Column|Collection is_enabled(string $label = null)
     * @method Grid\Column|Collection extension(string $label = null)
     * @method Grid\Column|Collection icon(string $label = null)
     * @method Grid\Column|Collection order(string $label = null)
     * @method Grid\Column|Collection parent_id(string $label = null)
     * @method Grid\Column|Collection uri(string $label = null)
     * @method Grid\Column|Collection input(string $label = null)
     * @method Grid\Column|Collection ip(string $label = null)
     * @method Grid\Column|Collection method(string $label = null)
     * @method Grid\Column|Collection path(string $label = null)
     * @method Grid\Column|Collection user_id(string $label = null)
     * @method Grid\Column|Collection menu_id(string $label = null)
     * @method Grid\Column|Collection permission_id(string $label = null)
     * @method Grid\Column|Collection http_method(string $label = null)
     * @method Grid\Column|Collection http_path(string $label = null)
     * @method Grid\Column|Collection slug(string $label = null)
     * @method Grid\Column|Collection role_id(string $label = null)
     * @method Grid\Column|Collection value(string $label = null)
     * @method Grid\Column|Collection avatar(string $label = null)
     * @method Grid\Column|Collection password(string $label = null)
     * @method Grid\Column|Collection remember_token(string $label = null)
     * @method Grid\Column|Collection username(string $label = null)
     * @method Grid\Column|Collection banner(string $label = null)
     * @method Grid\Column|Collection lang(string $label = null)
     * @method Grid\Column|Collection sort(string $label = null)
     * @method Grid\Column|Collection status(string $label = null)
     * @method Grid\Column|Collection vedio(string $label = null)
     * @method Grid\Column|Collection bigkey(string $label = null)
     * @method Grid\Column|Collection num(string $label = null)
     * @method Grid\Column|Collection content(string $label = null)
     * @method Grid\Column|Collection content_en(string $label = null)
     * @method Grid\Column|Collection content_fr(string $label = null)
     * @method Grid\Column|Collection title_en(string $label = null)
     * @method Grid\Column|Collection title_fr(string $label = null)
     * @method Grid\Column|Collection element(string $label = null)
     * @method Grid\Column|Collection help(string $label = null)
     * @method Grid\Column|Collection key(string $label = null)
     * @method Grid\Column|Collection rule(string $label = null)
     * @method Grid\Column|Collection tab(string $label = null)
     * @method Grid\Column|Collection depth(string $label = null)
     * @method Grid\Column|Collection zhi_num(string $label = null)
     * @method Grid\Column|Collection connection(string $label = null)
     * @method Grid\Column|Collection exception(string $label = null)
     * @method Grid\Column|Collection failed_at(string $label = null)
     * @method Grid\Column|Collection payload(string $label = null)
     * @method Grid\Column|Collection queue(string $label = null)
     * @method Grid\Column|Collection uuid(string $label = null)
     * @method Grid\Column|Collection insurance(string $label = null)
     * @method Grid\Column|Collection is_redeem(string $label = null)
     * @method Grid\Column|Collection multiple(string $label = null)
     * @method Grid\Column|Collection next_time(string $label = null)
     * @method Grid\Column|Collection ordernum(string $label = null)
     * @method Grid\Column|Collection over_income(string $label = null)
     * @method Grid\Column|Collection redeem_time(string $label = null)
     * @method Grid\Column|Collection ticket_id(string $label = null)
     * @method Grid\Column|Collection ticket_price(string $label = null)
     * @method Grid\Column|Collection total_income(string $label = null)
     * @method Grid\Column|Collection user_ticket_id(string $label = null)
     * @method Grid\Column|Collection wait_income(string $label = null)
     * @method Grid\Column|Collection coin_img(string $label = null)
     * @method Grid\Column|Collection contract_address(string $label = null)
     * @method Grid\Column|Collection contract_address_lp(string $label = null)
     * @method Grid\Column|Collection is_success(string $label = null)
     * @method Grid\Column|Collection is_sync(string $label = null)
     * @method Grid\Column|Collection pancake_cate(string $label = null)
     * @method Grid\Column|Collection precision(string $label = null)
     * @method Grid\Column|Collection rate(string $label = null)
     * @method Grid\Column|Collection gift_rank_id(string $label = null)
     * @method Grid\Column|Collection gift_ticket_id(string $label = null)
     * @method Grid\Column|Collection gift_ticket_num(string $label = null)
     * @method Grid\Column|Collection lv(string $label = null)
     * @method Grid\Column|Collection price(string $label = null)
     * @method Grid\Column|Collection sales(string $label = null)
     * @method Grid\Column|Collection static_rate(string $label = null)
     * @method Grid\Column|Collection stock(string $label = null)
     * @method Grid\Column|Collection hash(string $label = null)
     * @method Grid\Column|Collection pay_type(string $label = null)
     * @method Grid\Column|Collection finish_time(string $label = null)
     * @method Grid\Column|Collection pay_status(string $label = null)
     * @method Grid\Column|Collection pool(string $label = null)
     * @method Grid\Column|Collection equal_rate(string $label = null)
     * @method Grid\Column|Collection small_num(string $label = null)
     * @method Grid\Column|Collection coin_type(string $label = null)
     * @method Grid\Column|Collection date(string $label = null)
     * @method Grid\Column|Collection main_chain(string $label = null)
     * @method Grid\Column|Collection ticket_sale(string $label = null)
     * @method Grid\Column|Collection is_del(string $label = null)
     * @method Grid\Column|Collection is_fan(string $label = null)
     * @method Grid\Column|Collection is_platform(string $label = null)
     * @method Grid\Column|Collection symbol(string $label = null)
     * @method Grid\Column|Collection total_price(string $label = null)
     * @method Grid\Column|Collection group_num(string $label = null)
     * @method Grid\Column|Collection new_parent_id(string $label = null)
     * @method Grid\Column|Collection new_path(string $label = null)
     * @method Grid\Column|Collection old_parent_id(string $label = null)
     * @method Grid\Column|Collection old_path(string $label = null)
     * @method Grid\Column|Collection new_wallet(string $label = null)
     * @method Grid\Column|Collection old_wallet(string $label = null)
     * @method Grid\Column|Collection day(string $label = null)
     * @method Grid\Column|Collection ranking(string $label = null)
     * @method Grid\Column|Collection reward(string $label = null)
     * @method Grid\Column|Collection total(string $label = null)
     * @method Grid\Column|Collection month(string $label = null)
     * @method Grid\Column|Collection from_uid(string $label = null)
     * @method Grid\Column|Collection insurance_id(string $label = null)
     * @method Grid\Column|Collection source_type(string $label = null)
     * @method Grid\Column|Collection cate(string $label = null)
     * @method Grid\Column|Collection from_user_id(string $label = null)
     * @method Grid\Column|Collection msg(string $label = null)
     * @method Grid\Column|Collection code(string $label = null)
     * @method Grid\Column|Collection headimgurl(string $label = null)
     * @method Grid\Column|Collection hold_rank(string $label = null)
     * @method Grid\Column|Collection is_valid(string $label = null)
     * @method Grid\Column|Collection level(string $label = null)
     * @method Grid\Column|Collection node_rank(string $label = null)
     * @method Grid\Column|Collection rank(string $label = null)
     * @method Grid\Column|Collection self_num(string $label = null)
     * @method Grid\Column|Collection self_yeji(string $label = null)
     * @method Grid\Column|Collection small_yeji(string $label = null)
     * @method Grid\Column|Collection team_num(string $label = null)
     * @method Grid\Column|Collection team_yeji(string $label = null)
     * @method Grid\Column|Collection total_num(string $label = null)
     * @method Grid\Column|Collection total_yeji(string $label = null)
     * @method Grid\Column|Collection usdt(string $label = null)
     * @method Grid\Column|Collection wallet(string $label = null)
     * @method Grid\Column|Collection zhi_valid(string $label = null)
     * @method Grid\Column|Collection ac_amount(string $label = null)
     * @method Grid\Column|Collection fee(string $label = null)
     * @method Grid\Column|Collection fee_amount(string $label = null)
     * @method Grid\Column|Collection finsh_time(string $label = null)
     * @method Grid\Column|Collection receive_address(string $label = null)
     */
    class Grid {}

    class MiniGrid extends Grid {}

    /**
     * @property Show\Field|Collection created_at
     * @property Show\Field|Collection detail
     * @property Show\Field|Collection id
     * @property Show\Field|Collection name
     * @property Show\Field|Collection type
     * @property Show\Field|Collection updated_at
     * @property Show\Field|Collection version
     * @property Show\Field|Collection is_enabled
     * @property Show\Field|Collection extension
     * @property Show\Field|Collection icon
     * @property Show\Field|Collection order
     * @property Show\Field|Collection parent_id
     * @property Show\Field|Collection uri
     * @property Show\Field|Collection input
     * @property Show\Field|Collection ip
     * @property Show\Field|Collection method
     * @property Show\Field|Collection path
     * @property Show\Field|Collection user_id
     * @property Show\Field|Collection menu_id
     * @property Show\Field|Collection permission_id
     * @property Show\Field|Collection http_method
     * @property Show\Field|Collection http_path
     * @property Show\Field|Collection slug
     * @property Show\Field|Collection role_id
     * @property Show\Field|Collection value
     * @property Show\Field|Collection avatar
     * @property Show\Field|Collection password
     * @property Show\Field|Collection remember_token
     * @property Show\Field|Collection username
     * @property Show\Field|Collection banner
     * @property Show\Field|Collection lang
     * @property Show\Field|Collection sort
     * @property Show\Field|Collection status
     * @property Show\Field|Collection vedio
     * @property Show\Field|Collection bigkey
     * @property Show\Field|Collection num
     * @property Show\Field|Collection content
     * @property Show\Field|Collection content_en
     * @property Show\Field|Collection content_fr
     * @property Show\Field|Collection title_en
     * @property Show\Field|Collection title_fr
     * @property Show\Field|Collection element
     * @property Show\Field|Collection help
     * @property Show\Field|Collection key
     * @property Show\Field|Collection rule
     * @property Show\Field|Collection tab
     * @property Show\Field|Collection depth
     * @property Show\Field|Collection zhi_num
     * @property Show\Field|Collection connection
     * @property Show\Field|Collection exception
     * @property Show\Field|Collection failed_at
     * @property Show\Field|Collection payload
     * @property Show\Field|Collection queue
     * @property Show\Field|Collection uuid
     * @property Show\Field|Collection insurance
     * @property Show\Field|Collection is_redeem
     * @property Show\Field|Collection multiple
     * @property Show\Field|Collection next_time
     * @property Show\Field|Collection ordernum
     * @property Show\Field|Collection over_income
     * @property Show\Field|Collection redeem_time
     * @property Show\Field|Collection ticket_id
     * @property Show\Field|Collection ticket_price
     * @property Show\Field|Collection total_income
     * @property Show\Field|Collection user_ticket_id
     * @property Show\Field|Collection wait_income
     * @property Show\Field|Collection coin_img
     * @property Show\Field|Collection contract_address
     * @property Show\Field|Collection contract_address_lp
     * @property Show\Field|Collection is_success
     * @property Show\Field|Collection is_sync
     * @property Show\Field|Collection pancake_cate
     * @property Show\Field|Collection precision
     * @property Show\Field|Collection rate
     * @property Show\Field|Collection gift_rank_id
     * @property Show\Field|Collection gift_ticket_id
     * @property Show\Field|Collection gift_ticket_num
     * @property Show\Field|Collection lv
     * @property Show\Field|Collection price
     * @property Show\Field|Collection sales
     * @property Show\Field|Collection static_rate
     * @property Show\Field|Collection stock
     * @property Show\Field|Collection hash
     * @property Show\Field|Collection pay_type
     * @property Show\Field|Collection finish_time
     * @property Show\Field|Collection pay_status
     * @property Show\Field|Collection pool
     * @property Show\Field|Collection equal_rate
     * @property Show\Field|Collection small_num
     * @property Show\Field|Collection coin_type
     * @property Show\Field|Collection date
     * @property Show\Field|Collection main_chain
     * @property Show\Field|Collection ticket_sale
     * @property Show\Field|Collection is_del
     * @property Show\Field|Collection is_fan
     * @property Show\Field|Collection is_platform
     * @property Show\Field|Collection symbol
     * @property Show\Field|Collection total_price
     * @property Show\Field|Collection group_num
     * @property Show\Field|Collection new_parent_id
     * @property Show\Field|Collection new_path
     * @property Show\Field|Collection old_parent_id
     * @property Show\Field|Collection old_path
     * @property Show\Field|Collection new_wallet
     * @property Show\Field|Collection old_wallet
     * @property Show\Field|Collection day
     * @property Show\Field|Collection ranking
     * @property Show\Field|Collection reward
     * @property Show\Field|Collection total
     * @property Show\Field|Collection month
     * @property Show\Field|Collection from_uid
     * @property Show\Field|Collection insurance_id
     * @property Show\Field|Collection source_type
     * @property Show\Field|Collection cate
     * @property Show\Field|Collection from_user_id
     * @property Show\Field|Collection msg
     * @property Show\Field|Collection code
     * @property Show\Field|Collection headimgurl
     * @property Show\Field|Collection hold_rank
     * @property Show\Field|Collection is_valid
     * @property Show\Field|Collection level
     * @property Show\Field|Collection node_rank
     * @property Show\Field|Collection rank
     * @property Show\Field|Collection self_num
     * @property Show\Field|Collection self_yeji
     * @property Show\Field|Collection small_yeji
     * @property Show\Field|Collection team_num
     * @property Show\Field|Collection team_yeji
     * @property Show\Field|Collection total_num
     * @property Show\Field|Collection total_yeji
     * @property Show\Field|Collection usdt
     * @property Show\Field|Collection wallet
     * @property Show\Field|Collection zhi_valid
     * @property Show\Field|Collection ac_amount
     * @property Show\Field|Collection fee
     * @property Show\Field|Collection fee_amount
     * @property Show\Field|Collection finsh_time
     * @property Show\Field|Collection receive_address
     *
     * @method Show\Field|Collection created_at(string $label = null)
     * @method Show\Field|Collection detail(string $label = null)
     * @method Show\Field|Collection id(string $label = null)
     * @method Show\Field|Collection name(string $label = null)
     * @method Show\Field|Collection type(string $label = null)
     * @method Show\Field|Collection updated_at(string $label = null)
     * @method Show\Field|Collection version(string $label = null)
     * @method Show\Field|Collection is_enabled(string $label = null)
     * @method Show\Field|Collection extension(string $label = null)
     * @method Show\Field|Collection icon(string $label = null)
     * @method Show\Field|Collection order(string $label = null)
     * @method Show\Field|Collection parent_id(string $label = null)
     * @method Show\Field|Collection uri(string $label = null)
     * @method Show\Field|Collection input(string $label = null)
     * @method Show\Field|Collection ip(string $label = null)
     * @method Show\Field|Collection method(string $label = null)
     * @method Show\Field|Collection path(string $label = null)
     * @method Show\Field|Collection user_id(string $label = null)
     * @method Show\Field|Collection menu_id(string $label = null)
     * @method Show\Field|Collection permission_id(string $label = null)
     * @method Show\Field|Collection http_method(string $label = null)
     * @method Show\Field|Collection http_path(string $label = null)
     * @method Show\Field|Collection slug(string $label = null)
     * @method Show\Field|Collection role_id(string $label = null)
     * @method Show\Field|Collection value(string $label = null)
     * @method Show\Field|Collection avatar(string $label = null)
     * @method Show\Field|Collection password(string $label = null)
     * @method Show\Field|Collection remember_token(string $label = null)
     * @method Show\Field|Collection username(string $label = null)
     * @method Show\Field|Collection banner(string $label = null)
     * @method Show\Field|Collection lang(string $label = null)
     * @method Show\Field|Collection sort(string $label = null)
     * @method Show\Field|Collection status(string $label = null)
     * @method Show\Field|Collection vedio(string $label = null)
     * @method Show\Field|Collection bigkey(string $label = null)
     * @method Show\Field|Collection num(string $label = null)
     * @method Show\Field|Collection content(string $label = null)
     * @method Show\Field|Collection content_en(string $label = null)
     * @method Show\Field|Collection content_fr(string $label = null)
     * @method Show\Field|Collection title_en(string $label = null)
     * @method Show\Field|Collection title_fr(string $label = null)
     * @method Show\Field|Collection element(string $label = null)
     * @method Show\Field|Collection help(string $label = null)
     * @method Show\Field|Collection key(string $label = null)
     * @method Show\Field|Collection rule(string $label = null)
     * @method Show\Field|Collection tab(string $label = null)
     * @method Show\Field|Collection depth(string $label = null)
     * @method Show\Field|Collection zhi_num(string $label = null)
     * @method Show\Field|Collection connection(string $label = null)
     * @method Show\Field|Collection exception(string $label = null)
     * @method Show\Field|Collection failed_at(string $label = null)
     * @method Show\Field|Collection payload(string $label = null)
     * @method Show\Field|Collection queue(string $label = null)
     * @method Show\Field|Collection uuid(string $label = null)
     * @method Show\Field|Collection insurance(string $label = null)
     * @method Show\Field|Collection is_redeem(string $label = null)
     * @method Show\Field|Collection multiple(string $label = null)
     * @method Show\Field|Collection next_time(string $label = null)
     * @method Show\Field|Collection ordernum(string $label = null)
     * @method Show\Field|Collection over_income(string $label = null)
     * @method Show\Field|Collection redeem_time(string $label = null)
     * @method Show\Field|Collection ticket_id(string $label = null)
     * @method Show\Field|Collection ticket_price(string $label = null)
     * @method Show\Field|Collection total_income(string $label = null)
     * @method Show\Field|Collection user_ticket_id(string $label = null)
     * @method Show\Field|Collection wait_income(string $label = null)
     * @method Show\Field|Collection coin_img(string $label = null)
     * @method Show\Field|Collection contract_address(string $label = null)
     * @method Show\Field|Collection contract_address_lp(string $label = null)
     * @method Show\Field|Collection is_success(string $label = null)
     * @method Show\Field|Collection is_sync(string $label = null)
     * @method Show\Field|Collection pancake_cate(string $label = null)
     * @method Show\Field|Collection precision(string $label = null)
     * @method Show\Field|Collection rate(string $label = null)
     * @method Show\Field|Collection gift_rank_id(string $label = null)
     * @method Show\Field|Collection gift_ticket_id(string $label = null)
     * @method Show\Field|Collection gift_ticket_num(string $label = null)
     * @method Show\Field|Collection lv(string $label = null)
     * @method Show\Field|Collection price(string $label = null)
     * @method Show\Field|Collection sales(string $label = null)
     * @method Show\Field|Collection static_rate(string $label = null)
     * @method Show\Field|Collection stock(string $label = null)
     * @method Show\Field|Collection hash(string $label = null)
     * @method Show\Field|Collection pay_type(string $label = null)
     * @method Show\Field|Collection finish_time(string $label = null)
     * @method Show\Field|Collection pay_status(string $label = null)
     * @method Show\Field|Collection pool(string $label = null)
     * @method Show\Field|Collection equal_rate(string $label = null)
     * @method Show\Field|Collection small_num(string $label = null)
     * @method Show\Field|Collection coin_type(string $label = null)
     * @method Show\Field|Collection date(string $label = null)
     * @method Show\Field|Collection main_chain(string $label = null)
     * @method Show\Field|Collection ticket_sale(string $label = null)
     * @method Show\Field|Collection is_del(string $label = null)
     * @method Show\Field|Collection is_fan(string $label = null)
     * @method Show\Field|Collection is_platform(string $label = null)
     * @method Show\Field|Collection symbol(string $label = null)
     * @method Show\Field|Collection total_price(string $label = null)
     * @method Show\Field|Collection group_num(string $label = null)
     * @method Show\Field|Collection new_parent_id(string $label = null)
     * @method Show\Field|Collection new_path(string $label = null)
     * @method Show\Field|Collection old_parent_id(string $label = null)
     * @method Show\Field|Collection old_path(string $label = null)
     * @method Show\Field|Collection new_wallet(string $label = null)
     * @method Show\Field|Collection old_wallet(string $label = null)
     * @method Show\Field|Collection day(string $label = null)
     * @method Show\Field|Collection ranking(string $label = null)
     * @method Show\Field|Collection reward(string $label = null)
     * @method Show\Field|Collection total(string $label = null)
     * @method Show\Field|Collection month(string $label = null)
     * @method Show\Field|Collection from_uid(string $label = null)
     * @method Show\Field|Collection insurance_id(string $label = null)
     * @method Show\Field|Collection source_type(string $label = null)
     * @method Show\Field|Collection cate(string $label = null)
     * @method Show\Field|Collection from_user_id(string $label = null)
     * @method Show\Field|Collection msg(string $label = null)
     * @method Show\Field|Collection code(string $label = null)
     * @method Show\Field|Collection headimgurl(string $label = null)
     * @method Show\Field|Collection hold_rank(string $label = null)
     * @method Show\Field|Collection is_valid(string $label = null)
     * @method Show\Field|Collection level(string $label = null)
     * @method Show\Field|Collection node_rank(string $label = null)
     * @method Show\Field|Collection rank(string $label = null)
     * @method Show\Field|Collection self_num(string $label = null)
     * @method Show\Field|Collection self_yeji(string $label = null)
     * @method Show\Field|Collection small_yeji(string $label = null)
     * @method Show\Field|Collection team_num(string $label = null)
     * @method Show\Field|Collection team_yeji(string $label = null)
     * @method Show\Field|Collection total_num(string $label = null)
     * @method Show\Field|Collection total_yeji(string $label = null)
     * @method Show\Field|Collection usdt(string $label = null)
     * @method Show\Field|Collection wallet(string $label = null)
     * @method Show\Field|Collection zhi_valid(string $label = null)
     * @method Show\Field|Collection ac_amount(string $label = null)
     * @method Show\Field|Collection fee(string $label = null)
     * @method Show\Field|Collection fee_amount(string $label = null)
     * @method Show\Field|Collection finsh_time(string $label = null)
     * @method Show\Field|Collection receive_address(string $label = null)
     */
    class Show {}

    /**
     * @method \SuperEggs\DcatDistpicker\Form\Distpicker distpicker(...$params)
     */
    class Form {}

}

namespace Dcat\Admin\Grid {
    /**
     * @method $this lightbox(...$params)
     * @method $this video(...$params)
     * @method $this audio(...$params)
     * @method $this distpicker(...$params)
     */
    class Column {}

    /**
     * @method \SuperEggs\DcatDistpicker\Filter\DistpickerFilter distpicker(...$params)
     */
    class Filter {}
}

namespace Dcat\Admin\Show {
    /**
     * @method $this lightbox(...$params)
     * @method $this video(...$params)
     * @method $this audio(...$params)
     */
    class Field {}
}
