export function useImageFallback() {
    const withFallback = (fallbackSrc) => (event) => {
        const target = event?.target
        if (!target || target.dataset.fallbackApplied === '1') return
        target.dataset.fallbackApplied = '1'
        target.src = fallbackSrc
    }

    return { withFallback }
}

