"use client";

import React, { useEffect, useState, useRef } from "react";
import { motion, useInView } from "framer-motion";
import { Clock, Recycle, Users, Scale, Award, TrendingUp } from "lucide-react";
import { StatisticItem } from "@/types/landing-page";

interface StatisticsSectionProps {
  statistics: StatisticItem[];
}

const ICON_MAP: Record<string, React.ElementType> = {
  Clock,
  Recycle,
  Users,
  Scale,
  Award,
  TrendingUp,
};

function Counter({ targetValue, prefix = "", suffix = "" }: { targetValue: string; prefix?: string; suffix?: string }) {
  const [count, setCount] = useState(0);
  const ref = useRef<HTMLSpanElement>(null);
  const isInView = useInView(ref, { once: true });

  const numeric = parseInt(targetValue.replace(/[^0-9]/g, ""), 10) || 0;

  useEffect(() => {
    if (!isInView || numeric <= 0) return;

    let start = 0;
    const duration = 2000;
    const stepTime = 30;
    const steps = duration / stepTime;
    const increment = numeric / steps;

    const timer = setInterval(() => {
      start += increment;
      if (start >= numeric) {
        setCount(numeric);
        clearInterval(timer);
      } else {
        setCount(Math.floor(start));
      }
    }, stepTime);

    return () => clearInterval(timer);
  }, [isInView, numeric]);

  return (
    <span ref={ref} className="font-mono font-black">
      {prefix}
      {isInView ? count.toLocaleString("id-ID") : 0}
      {suffix}
    </span>
  );
}

export default function StatisticsSection({ statistics }: StatisticsSectionProps) {
  return (
    <section className="py-16 relative bg-gradient-to-r from-sky-500 via-sky-600 to-blue-600 text-white shadow-xl shadow-sky-500/10">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          {statistics.map((stat, idx) => {
            const IconComponent = ICON_MAP[stat.icon || ""] || TrendingUp;

            return (
              <motion.div
                key={stat.id}
                initial={false}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.2 }}
                transition={{ duration: 0.5, delay: idx * 0.1 }}
                className="p-6 rounded-3xl bg-white/15 backdrop-blur-md border border-white/20 text-center space-y-3 shadow-lg hover:bg-white/25 transition-colors"
              >
                <div className="w-12 h-12 mx-auto rounded-2xl bg-white/20 text-white flex items-center justify-center shadow-sm">
                  <IconComponent className="w-6 h-6" />
                </div>

                <div className="text-3xl sm:text-4xl lg:text-5xl text-white font-black">
                  <Counter
                    targetValue={stat.value}
                    prefix={stat.prefix || ""}
                    suffix={stat.suffix || ""}
                  />
                </div>

                <p className="text-xs sm:text-sm font-semibold text-sky-100">
                  {stat.label}
                </p>
              </motion.div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
