
  'use strict';

  /* ───────────────────────────────────────────────────────────────────── */
  /* BaseClass                                                             */
  /* ───────────────────────────────────────────────────────────────────── */

  class BaseClass {

    constructor() {
      this.setSomeProperty('value 1');
    }

    getSomeProperty() {
      return this.someProperty;
    }

    setSomeProperty(value) {
      this.someProperty = value;
    }

    static someStaticMethod() {
      return 'static value from BaseClass';
    }

  }

  /* ───────────────────────────────────────────────────────────────────── */
  /* InheritedСlass                                                        */
  /* ───────────────────────────────────────────────────────────────────── */

  class InheritedСlass extends BaseClass {

    constructor() {
      super();
      this.setSomeProperty('value 2');
    }

  }

  /* ───────────────────────────────────────────────────────────────────── */

  let instance_1 = new BaseClass;
  let instance_2 = new InheritedСlass;

  console.log(instance_1.getSomeProperty());
  console.log(instance_2.getSomeProperty());
  console.log(Object.getPrototypeOf(instance_1));
  console.log(Object.getPrototypeOf(instance_2));
