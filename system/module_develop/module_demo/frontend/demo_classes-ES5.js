
/* ───────────────────────────────────────────────────────────────────── */
/* BaseClass                                                             */
/* ───────────────────────────────────────────────────────────────────── */

function BaseClass() {
    this.setSomeProperty('value 1');
}

BaseClass.prototype.getSomeProperty = function () {
    return this.someProperty;
}

BaseClass.prototype.setSomeProperty = function (value) {
    this.someProperty = value;
}

/* ───────────────────────────────────────────────────────────────────── */
/* InheritedClass                                                        */
/* ───────────────────────────────────────────────────────────────────── */

function InheritedClass() {
    this.setSomeProperty('value 2');
}

InheritedClass.prototype = new BaseClass;

/* ───────────────────────────────────────────────────────────────────── */

var instance_1 = new BaseClass;
var instance_2 = new InheritedClass;

console.log(instance_1.getSomeProperty());
console.log(instance_2.getSomeProperty());
console.log(Object.getPrototypeOf(instance_1));
console.log(Object.getPrototypeOf(instance_2));
