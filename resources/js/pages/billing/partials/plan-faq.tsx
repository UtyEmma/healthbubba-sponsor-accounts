import { MinusIcon } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import type { FrequentlyAskedQuestion } from '@/types';

export function PlanFaq({
    questions,
}: {
    questions: FrequentlyAskedQuestion[];
}) {
    if (questions.length === 0) {
        return null;
    }

    return (
        <section className="pt-7" aria-labelledby="faq-heading">
            <h2 id="faq-heading" className="pb-3 text-lg font-semibold">
                All your questions, answered
            </h2>
            <Card>
                <CardContent className="divide-y p-0">
                    {questions.map(({ id, question, answer }) => (
                        <article key={id} className="px-6 py-5">
                            <div className="flex items-center justify-between gap-4">
                                <h3 className="text-sm font-medium">
                                    {question}
                                </h3>
                                <MinusIcon className="size-4 shrink-0 text-muted-foreground" />
                            </div>
                            <p className="pt-2 text-sm leading-5 whitespace-pre-line text-muted-foreground">
                                {answer}
                            </p>
                        </article>
                    ))}
                </CardContent>
            </Card>
        </section>
    );
}
