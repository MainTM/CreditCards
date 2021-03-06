<?PHP

/*
 * The plugin that allows you to use your credit card in PocketMine-MP.
 * Copyright (C) 2016 JellyBrick_
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
namespace CreditCards;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\utils\Utils;use pocketmine\command\PluginCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class CreditCards extends PluginBase implements Listener {
	public $config, $data;
	public $money;
	public $limit;
	public $limit_temp;
	public $limit_check;
	public $overdue;
	public $month;
	public $p;
	public function onEnable() {
		$data = $this->data;
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		@mkdir ( $this->getDataFolder () );
		$this->config = new Config ( $this->getDataFolder () . "CreditCards.yml", Config::YAML, [ 
				"Limit" => 100000,
				"Month" => $this->months (),
				"Cards" => [ ] 
		] );
		$this->data = $this->config->getAll ();
		if (! ($this->money = $this->getServer ()->getPluginManager ()->getPlugin ( "EconomyAPI" )) and ! ($this->money = $this->getServer ()->getPluginManager ()->getPlugin ( "EconomyAPI" ))) {
			$this->getLogger ()->info ( Color::RED . "EconomyAPI 플러그인이 없습니다... 플러그인을 비활성화 합니다" );
			$this->getServer ()->getPluginManager ()->disablePlugin ( $this );
		} else {
			$this->getLogger ()->info ( Color::BLUE . "EconomyAPI 플러그인을 감지했습니다...! 플러그인을 활성화 합니다!" );
		}
		$server = Server::getInstance ();
		$p = $server->getPlayer ( $player );
		if ($p instanceof Player) {
			$player = $p->getName ();
		}
		$this->monthDate (); 
		// $this->messages = $this->MessageLoad();
		//나중에 messages.yml 사용시 쓸 부분
	}
	public function saveYml() {
		$save = new Config ( $this->getDataFolder () . "CreditCards.yml", Config::YAML );
		$save->setAll ( $this->data );
		$save->save ();
	} /*
	   * public function MsgLoad()
	   * {
	   * $this->saveResource("messages.yml");
	   * return (new Config($this->getDataFolder()."messages.yml", Config::YAML))->getAll();
	   * }
	   */
	   //나중에 messages.yml 사용시 쓸 부분
	public function months() {
		$month_data = date ( "n" );
		$month_data_int = (int)$month_data;
		$arr2 = [
			"$month_data_int"
			];
		return $arr2;
	}
	public function monthDate() {
		$overdue = $this->data ["Cards"] [$name] ["Overdue"];
		$month = $data ["Month"];
		$mine = $this->$data ["Cards"] [$player->getName ()];
		$check_overdue = $data ["Cards"] [$mine] ["Overdue"];
		$check_limitover = $data ["Cards"] [$mine] ["Current_payments"];
		if ($this->months() != $month) {
			if ($check_overdue == 0) {
				$data ["Cards"] [$mine] = [ 
						"Current_payments" => 0,
						"Overdue" => 0 
				];
			} else {
				$data ["Cards"] [$mine] = [ 
						"Current_payments" => 0,
						"Overdue" => $overdue 
				];
			}
		}
		// 여기가 적힌 플레이어들에게서 돈 뺏어오는 부분 - 아직 못만듬
	}
	public function onDisable() {
		$this->saveYml ();
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$data = $this->data;
		$prefix = "[ 서버 ]";
		$limit = $this->data ["Limit"];
		
		$server = Server::getInstance ();
		$player = array_shift ( $args );
		$amount = array_shift ( $args );
		$p = $server->getPlayer ( $player );
		$name = strtolower ( $sender->getName () );
		if ($p instanceof Player) {
			$player = $p->getName ();
		}
		$result = $this->addMoney($name, $amount);
		switch ($command->getName ()) {
			case "신용결제" :
				if (! isset ( $args [0] )) {
					$sender->sendMessage ( Color::RED . "$prefix /신용결제 <돈을 줄 닉네임> <돈의 양>" );
					$sender->sendMessage ( Color::RED . "$prefix <>는 빼고 입력해주세요!" );
					$sender->sendMessage ( Color::RED . "$prefix 더 많은 정보를 보려면 /신용 도움말" );
					return true;
				}
				$Current_payments = $this->data ["Cards"] [$name] ["Current_payments"];
				$limit_check = $amount + $Current_payments;
				$limit_temp = $limit_check;
				$overdue = $this->data ["Cards"] [$name] ["Overdue"];
				switch ($result) {
					case -2 :
						$sender->sendMessage ( Color::RED . "$prefix 오류로 인해 승인이 취소되었습니다!" );
						break;
					case -1 :
						$sender->sendMessage ( Color::RED . "$prefix $player 님은 서버에 접속한 적이 없습니다." );
						break;
					case 1 :
						if($limit_check > $limit)
						{
							$sender->sendMessage ( Color::RED . "$prefix 한도를 초과하여 결제 할수 없습니다" );
						}
						if( $overdue > 1)
						{
							 $sender->sendMessage ( Color::RED . "$prefix 카드가 연체 되어 있어 사용이 불가능 합니다!" );
						}
						$sender->sendMessage ( Color::GOLD . "$prefix $player 님에게 신용카드로 $amount 만큼 결제 하였습니다!" );
						$name = strtolower ( $sender->getName () );
						$data ["Cards"] [$name] = [ 
								"Current_payments" => $Current_payments + $amount,
								"Overdue" => $overdue 
						];
						$sendername = $sender->getName ();
						if ($p instanceof Player) {
							$p->sendMessage ( Color::YELLOW . "$prefix  $sendername 님이 신용카드로 $amount 만큼 결제하였습니다!" );
							$this->addMoney($p, $amount);
						}
						break;
				}
			case "신용" :
				switch ($args [0]) {
					$Current_payments = $this->data ["Cards"] [$name] ["Current_payments"];
					if (! isset ( $args [0] )) {
						foreach ( $this->getHelp () as $help ) {
							$sender->sendMessage ( Color::DARK_GREEN . "$prefix $help" );
						}
					return true;
					}
					case "결제금액" :
						$sender->sendMessage ( Color::GREEN . "$prefix 여태까지 결제한 금액은 $Current_payments 입니다!" );
					case "도움말" :
						foreach ( $this->getHelp () as $help ) {
							$sender->sendMessage ( Color::DARK_GREEN . "$prefix $help" );
						}
					case "비용납부" :
						$money=$this->MyMoney($sender);
						$name=strtolower($sender->getName());
						if($this->myMoney($name < $Current_payments)
						{
							$sender->sendMessage ( Color::RED . "$prefix $Current_payments 만큼을 결제할 돈이 부족합니다!" );
							$sender->sendMessage ( Color::RED . "$prefix $name 님의 보유한 금액은" .$this->myMoney($name). "입니다!" );
						}
						$sendersmoney = $this->myMoney($name);
						$this->TakeMoney($name, $Current_payments);
						$sender->sendMessage ( Color::GREEN . "$prefix $Current_payments 만큼의 비용이 모두 납부되었습니다!" );
					        //구현 완료!
				}
		}
	}
	public function getHelp() {
		$arr = [ 
				"/신용결제 <닉네임> <돈 양>",
				"/신용 결제금액",
				"/신용 도움말",
                		"/신용 비용납부"
		];
		return $arr;
	}
	public function TakeMoney(Player $player,$money){
		$this->money->setMoney($player, $this->money->myMoney($player) - $money);
	}
	public function addMoney(Player $player,$money){
		$this->money->setMoney($player, $this->money->myMoney($player) + $money);
	}
	public function myMoney(Player $player){
		return $this->money->myMoney($player);
	}
}
?>
