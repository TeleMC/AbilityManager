<?php
namespace AbilityManager;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use SkillManager\SkillManager;
use TeleMoney\TeleMoney;
use TutorialManager\TutorialManager;
use UiLibrary\UiLibrary;

class AbilityManager extends PluginBase {
    private static $instance = null;
    //public $pre = "§e§l[ §f시스템 §e]§r§e";
    public $pre = "§e•";

    public static function getInstance() {
        return self::$instance;
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->ability = new Config($this->getDataFolder() . "Ability.yml", Config::YAML);
        $this->adata = $this->ability->getAll();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->money = TeleMoney::getInstance();
        $this->ui = UiLibrary::getInstance();
        $this->skill = SkillManager::getInstance();
        $this->tutorial = TutorialManager::getInstance();
    }

    public function onDisable() {
        $this->save();
    }

    public function save() {
        $this->ability->setAll($this->adata);
        $this->ability->save();
    }

    public function getInbornJob(string $name) {
        if ($this->isAbility($name)) {
            return $this->adata[$name]["천직"];
        } else return false;
    }

    public function isAbility(string $name) {
        if (!isset($this->adata[$name]["재능"]) or $this->adata[$name]["재능"] == "-") return false;
        else return true;
    }

    public function getBornPoint(string $name, string $born) {
        if (!isset($this->adata[$name]["재능치"][$born]))
            return 0;
        return $this->adata[$name]["재능치"][$born];
    }

    public function addBornPoint(string $name, string $born, int $amount) {
        if (!isset($this->adata[$name]["재능치"][$born]))
            $this->adata[$name]["재능치"][$born] = 0;
        $this->adata[$name]["재능치"][$born] += $amount;
        if ($born == "연사" || $born == "근력" || $born == "베기" || $born == "마력 응축" || $born == "리커버리") {
            if ($this->adata[$name]["재능치"][$born] >= 100 && !in_array($born, $this->getBorns($name))) {
                $this->adata[$name]["천재능"][] = $born;
                if (($player = $this->getServer()->getPlayer($name)) instanceof Player)
                    $player->sendMessage("{$this->pre} 재능, [ {$born} ] (을)를 익혔습니다!");
            }
        } elseif ($born == "검기" || $born == "마력탄" || $born == "성력") {
            if ($this->adata[$name]["재능치"][$born] >= 1000 && !in_array($born, $this->getBorns($name))) {
                $this->adata[$name]["천재능"][] = $born;
                if (($player = $this->getServer()->getPlayer($name)) instanceof Player)
                    $player->sendMessage("{$this->pre} 재능, [ {$born} ] (을)를 익혔습니다!");
            }
        } else {
            if (!in_array($born, $this->getBorns($name))) {
                $this->adata[$name]["천재능"][] = $born;
                if (($player = $this->getServer()->getPlayer($name)) instanceof Player)
                    $player->sendMessage("{$this->pre} 재능, [ {$born} ] (을)를 익혔습니다!");
            }
        }
        $this->skill->check_skill($name);
    }

    public function getBorns(string $name) {
        if (!isset($this->adata[$name]["천재능"]))
            return ["-"];
        return $this->adata[$name]["천재능"];
    }

    public function getAllBorns(string $name) {
        if (!isset($this->adata[$name]["재능치"]))
            return ["-"];
        return $this->adata[$name]["재능치"];
    }

    public function Check_1(Player $player, string $result, string $question) {
        $this->check[$player->getName()]["천직"] = $result;
        if ($player instanceof Player) {
            $form = $this->ui->ModalForm(function (Player $player, array $data) {
                if ($data[0]) {
                    //$this->setInbornJob($player, $this->check[$player->getName()]["천직"]);
                    $this->end[$player->getName()]["천직"] = $this->check[$player->getName()]["천직"];
                    $this->end[$player->getName()]["개인천성"] = [];
                    $this->end[$player->getName()]["개인천성"]["모험가의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["지혜의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["근력의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["민첩의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["마력의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["운의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["상인의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["제작의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["채집, 채광의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["멸망의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["전설의 축복"] = 0;
                    $this->end[$player->getName()]["개인천성"]["신의 축복"] = 0;
                    unset($this->check[$player->getName()]["천직"]);
                    $this->Question_2($player);
                } else {
                    unset($this->check[$player->getName()]["천직"]);
                    $this->Question_1($player);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§f정말로\n§c▶ §f{$question} §c◀\n§f(으)로 답변하시겠습니까?");
            $form->setButton1("§l§8[예]");
            $form->setButton2("§l§8[아니오]");
            $form->sendToPlayer($player);
        }
    }

    private function Question_2(Player $player) {
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
                if (!is_numeric($data[0])) {
                    $this->Question_2($player);
                    return;
                }
                if ($data[0] == 0) {
                    $this->Check_2($player, "대위자드의 도움을 요청한다.", 2, "지혜의 축복", 2);
                }
                if ($data[0] == 1) {
                    $this->Check_2($player, "스스로의 힘으로 푼다.", 2, "지혜의 축복", 3);
                }
                if ($data[0] == 2) {
                    $this->Check_2($player, "책을 찾아본다.", 2, "지혜의 축복", 2);
                }
                if ($data[0] == 3) {
                    $this->Check_2($player, "새로운 이론을 구축한다.", 2, "지혜의 축복", 2);
                }
                if ($data[0] == 4) {
                    $this->Check_2($player, "적당히 마법을 쓴다.", 2, "운의 축복", 1);
                }
                if ($data[0] == 5) {
                    $this->Check_2($player, "포기한다.", 2, "지혜의 축복", -3);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§l§cQ2. §r§f마법난제가 있을때 어떻게 하실건가요?\n");
            $form->addButton("§c▶ §8대위자드의 도움을 요청한다. §c◀");
            $form->addButton("§a▶ §8스스로의 힘으로 푼다. §a◀");
            $form->addButton("§b▶ §8책을 찾아본다. §b◀");
            $form->addButton("§e▶ §8새로운 이론을 구축한다. §e◀");
            $form->addButton("§e▶ §8적당히 마법을 쓴다. §e◀");
            $form->addButton("§e▶ §8포기한다. §e◀");
            $form->sendToPlayer($player);
        }
    }

    public function Check_2(Player $player, string $question, int $count, string $result, int $amount, string $result_1 = null, int $amount_1 = null, string $result_2 = null, int $amount_2 = null) {
        $this->check[$player->getName()]["재능"] = $result;
        $this->check[$player->getName()]["수치"] = $amount;
        if ($amount_1 !== null) {
            $this->check[$player->getName()]["재능_1"] = $result_1;
            $this->check[$player->getName()]["수치_1"] = $amount_1;
        }
        if ($amount_2 !== null) {
            $this->check[$player->getName()]["재능_2"] = $result_2;
            $this->check[$player->getName()]["수치_2"] = $amount_2;
        }
        $this->check[$player->getName()]["질문"] = $count;
        if ($player instanceof Player) {
            $form = $this->ui->ModalForm(function (Player $player, array $data) {
                if ($data[0]) {
                    if ($this->check[$player->getName()]["수치"] >= 0) $this->end[$player->getName()]["개인천성"][$this->check[$player->getName()]["재능"]] += $this->check[$player->getName()]["수치"];
                    if ($this->check[$player->getName()]["수치"] < 0) $this->end[$player->getName()]["개인천성"][$this->check[$player->getName()]["재능"]] -= $this->check[$player->getName()]["수치"];
                    if (isset($this->check[$player->getName()]["수치_1"])) {
                        if ($this->check[$player->getName()]["수치_1"] >= 0) $this->end[$player->getName()]["개인천성"][$this->check[$player->getName()]["재능_1"]] += $this->check[$player->getName()]["수치_1"];
                        if ($this->check[$player->getName()]["수치_1"] < 0) $this->end[$player->getName()]["개인천성"][$this->check[$player->getName()]["재능_1"]] -= $this->check[$player->getName()]["수치_1"];
                    }
                    if (isset($this->check[$player->getName()]["수치_2"])) {
                        if ($this->check[$player->getName()]["수치_2"] >= 0) $this->end[$player->getName()]["개인천성"][$this->check[$player->getName()]["재능_2"]] += $this->check[$player->getName()]["수치_2"];
                        if ($this->check[$player->getName()]["수치_2"] < 0) $this->end[$player->getName()]["개인천성"][$this->check[$player->getName()]["재능_2"]] += $this->check[$player->getName()]["수치_2"];
                    }
                    if ($this->check[$player->getName()]["질문"] == 2) $this->Question_3($player);
                    if ($this->check[$player->getName()]["질문"] == 3) $this->Question_4($player);
                    if ($this->check[$player->getName()]["질문"] == 4) $this->Question_5($player);
                    if ($this->check[$player->getName()]["질문"] == 5) $this->Question_6($player);
                    if ($this->check[$player->getName()]["질문"] == 6) $this->End($player);
                    unset($this->check[$player->getName()]);
                } else {
                    if ($this->check[$player->getName()]["질문"] == 2) $this->Question_2($player);
                    if ($this->check[$player->getName()]["질문"] == 3) $this->Question_3($player);
                    if ($this->check[$player->getName()]["질문"] == 4) $this->Question_4($player);
                    if ($this->check[$player->getName()]["질문"] == 5) $this->Question_5($player);
                    if ($this->check[$player->getName()]["질문"] == 6) $this->Question_6($player);
                    unset($this->check[$player->getName()]);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§f정말로\n§c▶ §f{$question} §c◀\n§f(으)로 답변하시겠습니까?");
            $form->setButton1("§l§8[예]");
            $form->setButton2("§l§8[아니오]");
            $form->sendToPlayer($player);
        }
    }

    private function Question_3(Player $player) {
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
                if (!is_numeric($data[0])) {
                    $this->Question_3($player);
                    return;
                }
                if ($data[0] == 0) {
                    $this->Check_2($player, "조용히 필기하며 공부한다.", 3, "지혜의 축복", 3);
                }
                if ($data[0] == 1) {
                    $this->Check_2($player, "학습보다는 질문에 치중한다.", 3, "지혜의 축복", 1, "마력의 축복", 4);
                }
                if ($data[0] == 2) {
                    $this->Check_2($player, "운동할 생각을한다.", 3, "근력의 축복", 2, "지혜의 축복", -2);
                }
                if ($data[0] == 3) {
                    $this->Check_2($player, "배경지식이 없어 이해를 하지 못 한다.", 3, "모험가의 축복", 2);
                }
                if ($data[0] == 4) {
                    $this->Check_2($player, "적당히 듣는다.", 3, "지혜의 축복", 1, "마력의 축복", 1);
                }
                if ($data[0] == 5) {
                    $this->Check_2($player, "내가 여기서 도대체 뭘하는건지 생각한다.", 3, "지혜의 축복", 1, "운의 축복", 1);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§l§cQ3. §r§f마법이론을 들을 때 어떤 생각을 하시나요?\n");
            $form->addButton("§c▶ §8조용히 필기하며 공부한다. §c◀");
            $form->addButton("§a▶ §8학습보다는 질문에 치중한다. §a◀");
            $form->addButton("§b▶ §8운동할 생각을한다. §b◀");
            $form->addButton("§e▶ §8배경지식이 없어 이해를 하지 못 한다. §e◀");
            $form->addButton("§e▶ §8적당히 듣는다. §e◀");
            $form->addButton("§e▶ §8내가 여기서 도대체 뭘하는건지 생각한다. §e◀");
            $form->sendToPlayer($player);
        }
    }

    private function Question_4(Player $player) {
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
                if (!is_numeric($data[0])) {
                    $this->Question_4($player);
                }
                if ($data[0] == 0) {
                    $this->Check_2($player, "윗몸 일으키기", 4, "근력의 축복", 3);
                }
                if ($data[0] == 1) {
                    $this->Check_2($player, "달리기", 4, "근력의 축복", 1, "민첩의 축복", 2);
                }
                if ($data[0] == 2) {
                    $this->Check_2($player, "배드민턴", 4, "근력의 축복", 1, "민첩의 축복", 2);
                }
                if ($data[0] == 3) {
                    $this->Check_2($player, "수영", 4, "근력의 축복", 2);
                }
                if ($data[0] == 4) {
                    $this->Check_2($player, "정신단련", 4, "마력의 축복", 2);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§l§cQ4. §r§f어떤 운동을 좋아하시나요?\n");
            $form->addButton("§c▶ §8윗몸 일으키기 §c◀");
            $form->addButton("§a▶ §8달리기 §a◀");
            $form->addButton("§b▶ §8배드민턴 §b◀");
            $form->addButton("§e▶ §8수영 §e◀");
            $form->addButton("§e▶ §8정신단련 §e◀");
            $form->sendToPlayer($player);
        }
    }

    private function Question_5(Player $player) {
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
                if (!is_numeric($data[0])) {
                    $this->Question_5($player);
                    return;
                }
                if ($data[0] == 0) {
                    $this->Check_2($player, "비싸게 올렸으나, 팔린다.", 5, "상인의 축복", 1, "운의 축복", 2);
                }
                if ($data[0] == 1) {
                    $this->Check_2($player, "매점매석을 한다.", 5, "상인의 축복", 2, "운의 축복", 1);
                }
                if ($data[0] == 2) {
                    $this->Check_2($player, "적자가 발생한다.", 5, "상인의 축복", -1, "모험가의 축복", 2);
                }
                if ($data[0] == 3) {
                    $this->Check_2($player, "장사를 하지 않는다.", 5, "모험가의 축복", 3);
                }
                if ($data[0] == 4) {
                    $this->Check_2($player, "적당한 가격에 올려도 안 팔린다.", 5, "모험가의 축복", 4);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§l§cQ4. §r§f물건을 판매할때, 어떤 일이 일어날까요?\n");
            $form->addButton("§c▶ §8비싸게 올렸으나, 팔린다. §c◀");
            $form->addButton("§a▶ §8매점매석을 한다. §a◀");
            $form->addButton("§b▶ §8적자가 발생한다. §b◀");
            $form->addButton("§e▶ §8장사를 하지 않는다. §e◀");
            $form->addButton("§e▶ §8적당한 가격에 올려도 안 팔린다. §e◀");
            $form->sendToPlayer($player);
        }
    }

    private function Question_6(Player $player) {
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
                if (!is_numeric($data[0])) {
                    $this->Question_6($player);
                    return;
                }
                if ($data[0] == 0) {
                    $this->Check_2($player, "전설의 검", 6, "제작의 축복", 3);
                }
                if ($data[0] == 1) {
                    $this->Check_2($player, "평범한 검", 6, "제작의 축복", 2);
                }
                if ($data[0] == 2) {
                    $this->Check_2($player, "미식별 검", 6, "운의 축복", 3);
                }
                if ($data[0] == 3) {
                    $this->Check_2($player, "저주의 검", 6, "멸망의 축복", 3);
                }
                if ($data[0] == 4) {
                    $this->Check_2($player, "지팡이를 만든다.", 6, "운의 축복", 1, "지혜의 축복", 1, "모험가의 축복", 1);
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("\n§l§cQ4. §r§f무기를 만들었습니다. 어떤 무기일까요?\n");
            $form->addButton("§c▶ §8전설의 검 §c◀");
            $form->addButton("§a▶ §8평범한 검 §a◀");
            $form->addButton("§b▶ §8미식별 검 §b◀");
            $form->addButton("§e▶ §8저주의 검 §e◀");
            $form->addButton("§e▶ §8지팡이를 만든다. §e◀");
            $form->sendToPlayer($player);
        }
    }

    private function End(Player $player) {
        if ($player instanceof Player) {
            $this->setInbornJob($player, $this->end[$player->getName()]["천직"]);
            foreach ($this->end[$player->getName()]["개인천성"] as $type => $info) {
                if ($info > 0) {
                    $this->addAbility($player, $type, $this->end[$player->getName()]["개인천성"][$type]);
                } else {
                    $this->reduceAbility($player, $type, $this->end[$player->getName()]["개인천성"][$type]);
                }
            }
            $arr = [];
            foreach ($this->end[$player->getName()]["개인천성"] as $type_1 => $info_1) {
                array_push($arr, $this->end[$player->getName()]["개인천성"][$type_1]);
            }
            unset($this->end[$player->getName()]);
            $point = max($arr);
            $ability = $this->getAbilitybyPoint($player, $point);
            $this->setAbility($player, $ability);
            $a = "\n§c▶ §f천직 : {$this->adata[$player->getName()]["천직"]}\n\n";
            $b = "§b▶ §f재능 : {$this->getAbility($player->getName())}\n\n";
            $c = "\n§c▶ §f주의! §r§f천직은 추천 직업일 뿐, 직업이 아닙니다.\n\n\n\n";
            $form = $this->ui->CustomForm(function (Player $player, array $data) {
            });
            $form->setTitle("Tele Ability");
            $form->addLabel("§a▶ §f천직과 재능이 선정되었습니다!");
            $form->addLabel($a);
            $form->addLabel($b);
            $form->addLabel($c);
            $form->sendToPlayer($player);
        }
    }

    private function setInbornJob(Player $player, string $type) {
        $this->adata[$player->getName()]["천직"] = $type;
    }

    private function addAbility(Player $player, string $type, int $amount) {
        $this->adata[$player->getName()]["개인천성"][$type] += $amount;
    }

    private function reduceAbility(Player $player, string $type, int $amount) {
        if ($this->adata[$player->getName()]["개인천성"][$type] < $amount) {
            $this->adata[$player->getName()]["개인천성"][$type] = 0;
        } else {
            $this->adata[$player->getName()]["개인천성"][$type] -= $amount;
        }
    }

    private function getAbilitybyPoint(Player $player, int $amount) {
        foreach ($this->adata[$player->getName()]["개인천성"] as $type => $info) {
            if ($info == $amount) {
                return $type;
            }
        }
    }

    private function setAbility(Player $player, string $type) {
        $this->adata[$player->getName()]["재능"] = $type;
    }

    public function getAbility(string $name) {
        if ($this->isAbility($name)) {
            return $this->adata[$name]["재능"];
        } else return false;
    }

    public function Question_1(Player $player) {
        if ($this->isAbility($player->getName())) return;
        unset($this->end[$player->getName()]);
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
                if (!is_numeric($data[0])) {
                    $this->Question_1($player);
                    return;
                }
                if ($data[0] == 0) {
                    $this->Check_1($player, "나이트", "전위에서 전장을 호령하는 나이트");
                }
                if ($data[0] == 1) {
                    $this->Check_1($player, "아처", "적의 허점을 파고드는 아처");
                }
                if ($data[0] == 2) {
                    $this->Check_1($player, "위자드", "다양한 속성을 구사하여 강력한 위자드");
                }
                if ($data[0] == 3) {
                    $this->Check_1($player, "프리스트", "죽음으로부터 생명을 보호하는 프리스트");
                }
            });
            $form->setTitle("Tele Ability");
            $form->setContent("§a▶ §f자신의 재능을 결정할 처음이자 마지막 기회입니다.\n  §f결정한 후, 변경이 어려우니 신중히 답해주세요.\n\n  §l§cQ1. §r§f어떤 직업을 꿈꾸시나요?\n");
            $form->addButton("§c▶ §8전위에서 전장을 호령하는 나이트 §c◀");
            $form->addButton("§a▶ §8적의 허점을 파고드는 아처 §a◀");
            $form->addButton("§b▶ §8다양한 속성을 구사하여 강력한 위자드 §b◀");
            $form->addButton("§e▶ §8죽음으로부터 생명을 보호하는 프리스트 §e◀");
            $form->sendToPlayer($player);
        }
    }
}
