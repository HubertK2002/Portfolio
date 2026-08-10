# Dlaczego warto pisać testy (nawet w małych projektach)

Krótka notatka o tym, co realnie daje pisanie testów.

## Co zyskujesz

- **Pewność przy zmianach** — refaktoryzujesz bez strachu, że coś po cichu przestanie działać.
- **Dokumentacja zachowania** — test pokazuje, *jak* kod ma się zachować, lepiej niż komentarz.
- **Szybsze debugowanie** — zamiast klikać po aplikacji, odtwarzasz błąd w jednym teście.

## Od czego zacząć w PHP

Najprościej od **PHPUnit** i testów jednostkowych logiki biznesowej:

```php
public function testCartTotal(): void
{
    $cart = new Cart();
    $cart->add(new Product('A', 1000), 2);
    $this->assertSame(2000, $cart->total());
}
```

## Zasada praktyczna

Nie testuj wszystkiego na 100%. Testuj to, co:

1. jest ważne biznesowo (płatności, zamówienia),
2. łatwo popsuć przy zmianach,
3. trudno sprawdzić ręcznie.
