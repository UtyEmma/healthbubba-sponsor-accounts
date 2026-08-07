import { MinusIcon } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';

const questions = [
    {
        question: 'How is coverage allocated to each employee?',
        answer: 'Unlike family plans, corporate coverage is isolated per employee. Each seat gets its own monthly allotment (2 GP on Business Basic; 3 GP + 1 specialist on Business Premium). One employee can never consume another employee’s unused consultations.',
    },
    {
        question: 'Do unused consultations roll over?',
        answer: 'No. All unused GP and specialist allocations expire and reset to zero at 00:00 on your monthly renewal date.',
    },
    {
        question: 'What happens when I upgrade or downgrade the plan?',
        answer: 'Upgrades between tiers are processed instantly with a pro-rata adjustment. Downgrades don’t take effect mid-cycle — they’re staged and apply at the next billing cycle to protect already-consumed allocations.',
    },
    {
        question: 'Can I cancel anytime?',
        answer: 'Yes. Coverage stays active until the end of the current billing cycle. Employees retain their own records and wallets afterward.',
    },
];

export function PlanFaq() {
    return (
        <section className="pt-7" aria-labelledby="faq-heading">
            <h2 id="faq-heading" className="pb-3 text-lg font-semibold">
                All your questions, answered
            </h2>
            <Card>
                <CardContent className="divide-y p-0">
                    {questions.map(({ question, answer }) => (
                        <article key={question} className="px-6 py-5">
                            <div className="flex items-center justify-between gap-4">
                                <h3 className="text-sm font-medium">
                                    {question}
                                </h3>
                                <MinusIcon className="size-4 shrink-0 text-muted-foreground" />
                            </div>
                            <p className="pt-2 text-sm leading-5 text-muted-foreground">
                                {answer}
                            </p>
                        </article>
                    ))}
                </CardContent>
            </Card>
        </section>
    );
}
