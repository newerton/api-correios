Biblioteca do Correios
=============

Está biblioteca está configurada para o funcionamento correto no Yii Framework.

Exemplo:
```php
$correio = new Correios;

$address["cep_origem"] = $cep_origem;

if (Yii::app()->user->isGuest) {
	$address["cep_destino"] = $cep;
} else {
	$address["cep_destino"] = Yii::app()->user->cep;
}

try {
	$rows = $correio->getQuote($address);
	$this->renderPartial("index", array("rows" => $rows));
} catch (Exception $exc) {
	echo $exc->getTraceAsString();
}
```

O funcionamento de buscar os produtos do carrinho:
```php
class Shop {

    public static function getCartContent() {
        if (is_string(Yii::app()->user->getState("carrinho")))
            return json_decode(Yii::app()->user->getState("carrinho"), true);
        else
            return Yii::app()->user->getState("carrinho");
    }

    public static function setCartContent($cart) {
        return Yii::app()->user->setState("carrinho", json_encode($cart));
    }
}
```

Controller que adiciona os produtos a sessão:
```php
class CarrinhoController extends Controller {

    public function actionAdd($id) {

        $new = true;
        $cart = Shop::getCartContent();

        if (!is_null($cart)) {
            foreach ($cart as $key => $value) {
                if (($value["id"] == $id)) {
                    $new = false;
                    $cart[$key]["quant"] += 1;
                }
            }
        }

        if ($new)
            $cart[] = array("id" => $id, "quant" => 1);

        Shop::setCartcontent($cart);

        $this->redirect(Yii::app()->createAbsoluteUrl("cart"));
    }
	
}
```
