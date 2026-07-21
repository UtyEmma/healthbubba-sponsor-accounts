export function BrandMark({ showName = false }: { showName?: boolean }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-lime">
                <img
                    src="/images/sponsor/logo.svg"
                    alt=""
                    className="size-[25px]"
                />
            </span>
            {showName && (
                <span className="flex flex-col">
                    <strong className="text-[13px] leading-[18px] font-medium text-ink">
                        HealthBubba
                    </strong>
                    <span className="text-[11px] leading-[14px] font-medium tracking-[.05em] text-muted-foreground uppercase">
                        Sponsor Portal
                    </span>
                </span>
            )}
        </div>
    );
}
