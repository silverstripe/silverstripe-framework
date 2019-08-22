import { useRef, useEffect } from 'react';

const usePrevious = (value) => {
    const ref = useRef()
    useEffect(() => void (ref.current = value), [value])
    return ref.current
};

export default usePrevious;