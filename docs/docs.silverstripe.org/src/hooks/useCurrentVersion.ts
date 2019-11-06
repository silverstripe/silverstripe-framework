const useCurrentVersion = (): string => {
    if (typeof window !== 'undefined') {
        const host = window.location.hostname;
        if (host === process.env.HOST_3x) {
            return '3x';
        }    
    }

    return '4x';
};

export default useCurrentVersion;