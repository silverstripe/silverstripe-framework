jest.dontMock('../reducer-register');

var reducerRegister = require('../reducer-register').default;

describe('ReducerRegister', () => {

    var reducer = () => null;

    it('should add a reducer to the register', () => {
        expect(reducerRegister.getAll().test).toBe(undefined);

        reducerRegister.add('test', reducer);
        expect(reducerRegister.getAll().test).toBe(reducer);

        reducerRegister.remove('test');
    });

    it('should remove a reducer from the register', () => {
        reducerRegister.add('test', reducer);
        expect(reducerRegister.getAll().test).toBe(reducer);

        reducerRegister.remove('test');
        expect(reducerRegister.getAll().test).toBe(undefined);
    });

    it('should get all reducers from the register', () => {
        reducerRegister.add('test1', reducer);
        reducerRegister.add('test2', reducer);

        expect(reducerRegister.getAll().test1).toBe(reducer);
        expect(reducerRegister.getAll().test2).toBe(reducer);

        reducerRegister.remove('test1');
        reducerRegister.remove('test2');
    });

    it('should get a single reducer from the register', () => {
        reducerRegister.add('test', reducer);
        expect(reducerRegister.getByKey('test')).toBe(reducer);

        reducerRegister.remove('test');
    });

});
